<?php

namespace splitbrain\JiraDash\Service;

use GuzzleHttp\Client;

class TempoAPI
{
    /** @var Client jira api client */
    protected $client;

    /**
     * TempoAPI constructor.
     *
     * @param string $user
     * @param string $pass
     * @param string $host
     */
    public function __construct($token)
    {
        $this->client = new Client([
            'base_uri' => 'https://api.tempo.io/',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer $token"
            ],
        ]);
    }

    /**
     * Run a request and return all results
     *
     * Handles paging
     *
     * @param $endpoint
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function query($endpoint, $params)
    {
        $result = array();
        $startAt = 0;
        do {

            $data = $this->apicall($endpoint, $params, $startAt);
            $result = array_merge_recursive($result, $data);
            $startAt += $data['metadata']['count'];
        } while (!empty($data['metadata']['next']));

        return $result;
    }

    /**
     * Run a single query against the API and return the result
     *
     * @param string $endpoint
     * @param array $query
     * @param int $from
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function apicall($endpoint, $query, $from = 0)
    {
        $query['limit'] = 1000;
        $query['offset'] = $from;

        $response = $this->client->request('GET', $endpoint, ['query' => $query]);

        if ($response->getStatusCode() > 299) {
            throw new \RuntimeException(
                'Status ' . $response->getStatusCode() . " GET\n" .
                $endpoint . "\n" .
                $response->getBody()->getContents(),
                $response->getStatusCode()
            );
        }

        return json_decode((string)$response->getBody(), true);
    }
}
