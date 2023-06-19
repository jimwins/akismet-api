<?php
namespace Akismet;

class API
{
  private \GuzzleHttp\Client $client;

  static private string $base_uri = 'https://rest.akismet.com';

  public function __construct(
    private string $api_key,
    private string $blog
  ) {
    $this->client = new \GuzzleHttp\Client([
      'base_uri' => self::$base_uri,
    ]);
  }

  /**
   * This method handles calling the Akismet API, adding the API key and blog
   * parameters, and catching standard API response errors and turning them
   * into exceptions.
   */
  private function call(string $path, mixed $params = []) : \Psr\Http\Message\ResponseInterface {
    $form_params = array_merge($params, [
      'api_key' => $this->api_key,
      'blog' => $this->blog,
    ]);

    $response = $this->client->post($path, [
      'form_params' => $form_params
    ]);

    if ($response->hasHeader('X-akismet-alert-code')) {
      $code = $response->getHeader('X-akismet-alert-code')[0];
      $message = $response->getHeader('X-akismet-alert-msg')[0];

      throw new Exception($message, (int)$code);
    }

    return $response;
  }

  /*
   * https://akismet.com/developers/key-verification/
   */
  public function verifyKey(): bool {
    $response = $this->call('/1.1/verify-key');

    $valid = (string)$response->getBody();

    if ($valid == 'valid') {
      return true;
    }

    return false;
  }

  /*
   * https://akismet.com/developers/comment-check/
   *
   * The underlying API is called "Comment check" but we call it isSpam() to
   * make clear what the result means.
   *
   * All of the params are as documented, but we also take a RequestInterface and
   * fill in values from that where we can.
   */
  public function isSpam(mixed $values, \Psr\Http\Message\ServerRequestInterface $request): int {
    /* Fill in Client IP if we didn't already */
    if (!array_key_exists('user_ip', $values)) {
      $values['user_ip'] = $request->getServerParams()['REMOTE_ADDR'];
    }

    /* Same with User-agent */
    if (!array_key_exists('user_agent', $values) &&
        $request->hasHeader('User-agent'))
    {
      $values['user_agent'] = $request->getHeader('User-agent')[0];
    }

    /* And referrer */
    if (!array_key_exists('referrer', $values) &&
        $request->hasHeader('Referer'))
    {
      // Yes, it's spelled wrong as a header, and always has been.
      $values['referrer'] = $request->getHeader('Referer')[0];
    }

    /* Like the official WordPress plugin, we pass through all POST variables
     * from the original request. */
    foreach ($request->getParsedBody() as $key => $value) {
      if (is_string($value)) {
        $values['POST_' . $key] = $value;
      }
    }

    /* And SERVER vars that are useful and probably not dangerous */
    $include= '/^(HTTP_|REMOTE_ADDR|REQUEST_URI|DOCUMENT_URI)/';
    $exclude= '/^(HTTP_COOKIE)/';
    foreach ($request->getServerParams() as $key => $value) {
      if (is_string($value) && preg_match($include, $key) && !preg_match($exclude, $key)) {
        $values[$key] = $value;
      }
    }

    $response = $this->call('/1.1/comment-check', $values);

    $valid = (string)$response->getBody();

    if ($valid == 'true') {
      if ($response->hasHeader('X-akismet-pro-tip') &&
          $response->getHeader('X-akismet-pro-tip')[0] == 'discard')
      {
        /* Akismet is so sure it's spam, they recommend just tossing it. */
        return 2;
      }
      return 1;
    }

    return 0;
  }

  /*
   * https://akismet.com/developers/submit-spam-missed-spam/
   */
  public function submitSpam(mixed $values): bool {
    $response = $this->call('/1.1/submit-spam', $values);

    $valid = (string)$response->getBody();

    if ($valid == 'Thanks for making the web a better place.') {
      return true;
    }

    return false;
  }

  /*
   * https://akismet.com/developers/submit-ham-false-positives/
   */
  public function submitHam(mixed $values): bool {
    $response = $this->call('/1.1/submit-ham', $values);

    $valid = (string)$response->getBody();

    if ($valid == 'Thanks for making the web a better place.') {
      return true;
    }

    return false;
  }

  /*
   * https://akismet.com/developers/key-sites-activity/
   *
   * We don't allow format to be specified, we want to work with JSON and
   * decode that.
   */
  public function getActivity(
    string|null $month = null,
    string $order = 'total',
    int $limit = 500,
    int $offset = 0
  ): mixed {
    $response = $this->call('/1.2/key-sites', [
      'month' => $month,
      'order' => $order,
      'limit' => $limit,
      'offset' => $offset,
    ]);

    $activity= json_decode($response->getBody());

    if (json_last_error() != JSON_ERROR_NONE) {
      throw new Exception(json_last_error_msg());
    }

    return $activity;
  }

  /*
   * https://akismet.com/developers/usage-limit/
   */
  public function getUsageLimit(): mixed {
    $response = $this->call('/1.2/usage-limit');

    $usage= json_decode($response->getBody());

    if (json_last_error() != JSON_ERROR_NONE) {
      throw new Exception(json_last_error_msg());
    }

    return $usage;
  }
}
