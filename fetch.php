<?php

class CountryDataFetcher
{
    const BASE_URL = 'http://api.worldbank.org/v2/country?format=json';

    private $countryData;

    public function __construct()
    {
        $this->countryData = [];
    }

    private static function fetch(string $url) {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
        ]);
    
        $result = curl_exec($ch);
        $code = curl_getinfo($ch);
    
        if (curl_error($ch)) {
            echo curl_error($ch) . "\n";
            exit(1);
        }
        else if ($code['http_code'] !== 200) {
            echo "Result is not http_code 200: " . $code['http_code'] .  "\n";
            exit(1);
        }
        
        curl_close($ch);
   
        $data = json_decode($result, true);
    
        return $data;
    }

    private function processPage(array $data)
    {
        foreach ($data[1] as $item) {
            if (preg_match('/^[A-Z]{2}$/', $item['iso2Code'])) {
                $this->countryData[] = $item;
            }
        }
    }

    public function getData()
    {
        $start = self::fetch(self::BASE_URL);
        $pages = $start[0]['pages'];

        $this->processPage($start);
        
        for ($page = 1; $page < $start[0]['pages']; $page++) {
            $data = self::fetch(self::BASE_URL . '&page=' . $page);
            $this->processPage($data);
        }
    }

    public function output()
    {
        $output = [];

        foreach ($this->countryData as $country) {
            $output[] = [
                'code' => $country['iso2Code'],
                'name' => $country['name'],
                'longitude' => $country['longitude'],
                'latitude' => $country['latitude'],
            ];
        }

        return $output;
    }
}


$cdf = new CountryDataFetcher();
$cdf->getData();

file_put_contents('data.json', json_encode($cdf->output(), JSON_PRETTY_PRINT));
