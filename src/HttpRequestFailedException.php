<?php

namespace Autowp\ImageHostClient;

use Zend\Http;

class HttpRequestFailedException extends Exception
{
    public function __construct(Http\Response $response)
    {
        parent::__construct(sprintf(
            'HTTP request failed: %s %s',
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));
    }
}
