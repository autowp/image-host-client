<?php

namespace Autowp\ImageHostClient;

use Zend\Http;
use Zend\Json\Json;

class HttpRequestFailedException extends Exception
{
    public function __construct(Http\Response $response)
    {
        $errors = [];

        if ($response->getStatusCode() == 400) {
            try {
                $json = Json::decode($response->getBody(), Json::TYPE_ARRAY);

                if (isset($json['invalid_params']) && is_array($json['invalid_params'])) {
                    foreach ($json['invalid_params'] as $field => $fieldErrors) {
                        $errors[] = $field . ': ' . implode(', ', $fieldErrors);
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        $message = sprintf(
            'HTTP request failed: %s %s',
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        if ($errors) {
            $message .= '. ' . implode('; ', $errors);
        }

        parent::__construct($message);
    }
}
