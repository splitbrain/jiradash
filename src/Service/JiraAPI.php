<?php

namespace splitbrain\JiraDash\Service;

use GuzzleHttp\Client;

class JiraAPI
{
    /** @var Client jira api client */
    protected $client;

    /**
     * JiraAPI constructor.
     *
     * @param string $user
     * @param string $pass
     * @param string $host
     */
    public function __construct($user, $pass, $host)
    {
        $this->client = new Client([
            'base_uri' => $host,
            'auth' => [$user, $pass],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * @param $endpoint
     * @param $jql
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function queryJQL($endpoint, $jql)
    {
        return $this->query($endpoint, ['jql' => $jql]);
    }

    /**
     * Run a JQL request and return all results
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

            $total = $data['total'];
            $startAt += $data['maxResults'];
        } while ($startAt < $total);

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
        $query['maxResults'] = 1000;
        $query['startAt'] = $from;

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
