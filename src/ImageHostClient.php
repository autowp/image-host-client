<?php

namespace Autowp\ImageHostClient;

use Imagick;

use Zend\Http;

use Autowp\Image;
use Zend\Json\Json;

class ImageHostClient implements Image\StorageInterface
{
    /**
     * @var Http\Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->httpClient = new Http\Client();
    }

    private function getApiUrl(string $path)
    {
        return 'http://' . $this->host . ':' . $this->port . '/api/' . ltrim($path, '/');
    }

    /**
     * @throws Exception
     * @return Storage\Image|null
     */
    public function getImage(int $imageId)
    {
        $response = $this->httpClient->reset()
            ->setMethod(Http\Request::METHOD_GET)
            ->setUri($this->getApiUrl('image/' . $imageId))
            ->send();

        if ($response->isNotFound()) {
            return null;
        }

        if (! $response->isSuccess()) {
            throw new HttpRequestFailedException($response);
        }

        $json = Json::decode($response->getBody(), Json::TYPE_ARRAY);

        return new Image\Storage\Image($json);
    }

    public function getImages(array $imageIds): array
    {
        $response = $this->httpClient->reset()
            ->setMethod(Http\Request::METHOD_GET)
            ->setUri($this->getApiUrl('image'))
            ->setParameterGet([
                'id' => $imageIds
            ])
            ->send();

        if (! $response->isSuccess()) {
            throw new HttpRequestFailedException($response);
        }

        $json = Json::decode($response->getBody(), Json::TYPE_ARRAY);

        $result = [];
        foreach ($json['items'] as $item) {
            $result[] = $item === null ? null : new Image\Storage\Image($item);
        }

        return $result;
    }

    /**
     * @return string|null
     */
    public function getImageBlob(int $imageId)
    {
        $response = $this->httpClient->reset()
            ->setMethod(Http\Request::METHOD_GET)
            ->setUri($this->getApiUrl('image/' . $imageId . '/file'))
            ->send();

        if ($response->isNotFound()) {
            return null;
        }

        if (! $response->isSuccess()) {
            throw new HttpRequestFailedException($response);
        }

        return $response->getBody();
    }

    /**
     * @param int|Storage\Request $imageId
     * @return string
     * @throws Exception
     */
    public function getFormatedImageBlob($request, string $formatName)
    {
        $image = $this->getFormatedImage($request, $formatName);

        if (! $image) {
            return null;
        }

        return file_get_contents($image->getSrc());
    }

    /**
     * @param int|Storage\Request $request
     * @param string $format
     * @return Image
     */
    public function getFormatedImage($request, string $formatName)
    {
        $result = $this->getFormatedImages([$request], $formatName);
        if (count($result) <= 0) {
            return null;
        }

        return $result[0];
    }

    /**
     * @param array $images
     * @param string $format
     * @return array
     */
    public function getFormatedImages(array $requests, string $formatName)
    {
        $response = $this->httpClient->reset()
            ->setMethod(Http\Request::METHOD_GET)
            ->setUri($this->getApiUrl('format'))
            ->setParameterGet([
                'id'     => $requests,
                'format' => $formatName
            ])
            ->send();

        if (! $response->isSuccess()) {
            throw new HttpRequestFailedException($response);
        }

        $json = Json::decode($response->getBody(), Json::TYPE_ARRAY);

        $result = [];
        foreach ($json['items'] as $item) {
            $result[] = $item === null ? null : new Image\Storage\Image($item);
        }

        return $result;
    }

    /**
     * @param int $imageId
     * @return Image
     * @throws Exception
     */
    public function removeImage(int $imageId)
    {
        $response = $this->httpClient->reset()
            ->setMethod(Http\Request::METHOD_DELETE)
            ->setUri($this->getApiUrl('image/' . $imageId))
            ->send();

        if (! $response->isSuccess()) {
            throw new HttpRequestFailedException($response);
        }
    }

    /**
     * @throws Exception
     */
    private function processUploadResponse(Http\Response $response): int
    {
        if (! $response->isSuccess()) {
            throw new HttpRequestFailedException($response);
        }

        $uri = $response->getHeaders()->get('Location')->uri();
        $parts = explode('/', $uri->getPath());

        if (count($parts) <= 0) {
            throw new Exception("Location uri path is invalid");
        }

        $imageId = $parts[count($parts) - 1];

        if (! is_numeric($imageId)) {
            throw new Exception("Location uri path is invalid");
        }

        return $imageId;
    }

    /**
     * @throws Exception
     */
    public function addImageFromBlob(string $blob, string $dirName, array $options = []): int
    {
        $defaults = [
            'name' => null
        ];
        $options = array_replace($defaults, $options);

        $params = [
            'dir' => $dirName
        ];

        if ($options['name']) {
            $params['name'] = $options['name'];
        }

        $response = $this->httpClient->reset()
            ->setFileUpload('image.jpg', 'file', $blob)
            ->setMethod(Http\Request::METHOD_POST)
            ->setParameterPost($params)
            ->setUri($this->getApiUrl('image'))
            ->send();

        return $this->processUploadResponse($response);
    }

    /**
     * @throws Exception
     */
    public function addImageFromImagick(Imagick $imagick, string $dirName, array $options = []): int
    {
        return $this->addImageFromBlob($imagick->getImageBlob(), $dirName, $options);
    }

    /**
     * @throws Exception
     */
    public function addImageFromFile(string $file, string $dirName, array $options = []): int
    {
        $defaults = [
            'name' => null
        ];
        $options = array_replace($defaults, $options);

        $params = [
            'dir' => $dirName
        ];

        if ($options['name']) {
            $params['name'] = $options['name'];
        }

        $response = $this->httpClient->reset()
            ->setFileUpload($file, 'file')
            ->setMethod(Http\Request::METHOD_POST)
            ->setParameterPost($params)
            ->setUri($this->getApiUrl('image'))
            ->send();

        return $this->processUploadResponse($response);
    }

    public function flush(array $options)
    {
        $defaults = [
            'format' => null,
            'image'  => null,
        ];

        $options = array_replace($defaults, $options);

        $query = [];
        if ($options['format']) {
            $query['format'] = (string)$options['format'];
        }

        if ($options['image']) {
            $query['id'] = $options['image'];
        }

        $response = $this->httpClient->reset()
            ->setMethod(Http\Request::METHOD_DELETE)
            ->setUri($this->getApiUrl('image/format'))
            ->setParameterGet($query)
            ->send();

        if (! $response->isSuccess()) {
            throw new HttpRequestFailedException($response);
        }
    }

    public function getImageIPTC(int $imageId)
    {
        $response = $this->httpClient->reset()
            ->setMethod(Http\Request::METHOD_GET)
            ->setUri($this->getApiUrl('image/' . $imageId))
            ->setParameterGet(['fields' => 'iptc'])
            ->send();

        if (! $response->isSuccess()) {
            throw new HttpRequestFailedException($response);
        }

        $json = Json::decode($response->getBody(), Json::TYPE_ARRAY);

        return $json['iptc'];
    }

    public function getImageEXIF(int $imageId)
    {
        $response = $this->httpClient->reset()
            ->setMethod(Http\Request::METHOD_GET)
            ->setUri($this->getApiUrl('image/' . $imageId))
            ->setParameterGet(['fields' => 'exif'])
            ->send();

        if (! $response->isSuccess()) {
            throw new HttpRequestFailedException($response);
        }

        $json = Json::decode($response->getBody(), Json::TYPE_ARRAY);

        return $json['exif'];
    }

    public function getImageResolution(int $imageId)
    {
        $response = $this->httpClient->reset()
            ->setMethod(Http\Request::METHOD_GET)
            ->setUri($this->getApiUrl('image/' . $imageId))
            ->setParameterGet(['fields' => 'resolution'])
            ->send();

        if (! $response->isSuccess()) {
            throw new HttpRequestFailedException($response);
        }

        $json = Json::decode($response->getBody(), Json::TYPE_ARRAY);

        return $json['resolution'];
    }

    /**
     * @throws Exception
     */
    public function changeImageName(int $imageId, array $options = [])
    {
        $params = [];

        if ($options['name']) {
            $params['name'] = $options['name'];
        }

        if ($params) {
            $response = $this->httpClient->reset()
                ->setMethod(Http\Request::METHOD_PUT)
                ->setUri($this->getApiUrl('image/' . $imageId))
                ->setParameterPost($params)
                ->send();

            if (! $response->isSuccess()) {
                throw new HttpRequestFailedException($response);
            }
        }
    }

    public function flop(int $imageId)
    {
        $response = $this->httpClient->reset()
            ->setMethod(Http\Request::METHOD_PUT)
            ->setUri($this->getApiUrl('image/' . $imageId))
            ->setParameterPost(['flop' => true])
            ->send();

        if (! $response->isSuccess()) {
            throw new HttpRequestFailedException($response);
        }
    }

    public function normalize(int $imageId)
    {
        $response = $this->httpClient->reset()
            ->setMethod(Http\Request::METHOD_PUT)
            ->setUri($this->getApiUrl('image/' . $imageId))
            ->setParameterPost(['normalize' => true])
            ->send();

        if (! $response->isSuccess()) {
            throw new HttpRequestFailedException($response);
        }
    }
}
