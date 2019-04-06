<?php

require __DIR__ . '/../vendor/autoload.php';
use Elasticsearch\ClientBuilder;
use Aws\Credentials\Credentials;
use Aws\Credentials\CredentialProvider;
use Aws\ElasticsearchService\ElasticsearchPhpHandler;

class AvantElasticsearchClient extends AvantElasticsearch
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function create(array $options = array())
    {
        // Use this to set the CURLOPT_TIMEOUT to limit how long requests may take.
        $timeout = isset($options['timeout']) ? $options['timeout'] : 90;

        // NOTE: there seems to be an issue with HTTP HEAD requests timing out
        // unles CURLOPT_NOBODY is set to true. Ideally this should be handled
        // by the elasticsearch connection object, but for now this is the workaround.
        $nobody = isset($options['nobody']) ? $options['nobody'] : false;

        $builder = ClientBuilder::create();

        // Hosts
        $hosts = self::getHosts();
        if (isset($hosts))
        {
            $builder->setHosts($hosts);
        }

        // Handler
        $handler = self::getHandler();
        if (isset($handler))
        {
            $builder->setHandler($handler);
        }

        // Connection Params
        $builder->setConnectionParams([
            'client' => [
                'curl' => [CURLOPT_TIMEOUT => $timeout, CURLOPT_NOBODY => $nobody]
            ]
        ]);

        // Return the Client object
        return $builder->build();
    }

    public static function getHosts()
    {
        $hostPath = 'search-digitalarchive-6wn5q4bmsxnikvykh7xiswwo4q.us-east-2.es.amazonaws.com';
        $port = '443';

        $host = [
            'host' => $hostPath,
            'port' => $port,
            'scheme' => "https",
            'user' => '',
            'pass' => ''
        ];

        return [$host];
    }

    public static function getHandler()
    {
        // Provide a signing handler for use with the official Elasticsearch-PHP client.
        // The handler will load AWS credentials and send requests using a RingPHP cURL handler.
        // Without this handler, a curl request to Elasticsearch on AWS will return a 403 Forbidden response.

        $key = 'AKIAQ2V2PCHKL5FIQNUD';
        $secret = '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';
        $creds = new Credentials($key, $secret);
        $region = 'us-east-2';
        $provider = CredentialProvider::fromCredentials($creds);

        return new ElasticsearchPhpHandler($region, $provider);
    }
}