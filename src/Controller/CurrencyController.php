<?php 

namespace App\Controller;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Currency;

class CurrencyController
{
    const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'CHF', 'GBP'];

    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * @param HttpClientInterface  $client
     */
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Index action
     * 
     * @param string  $currency
     * @param string  $startDate
     * @param string  $endDate
     * 
     * @return JsonResponse
     */
    public function indexAction(string $currency, string $startDate, string $endDate): JsonResponse
    {
        if ( ! in_array($currency, self::SUPPORTED_CURRENCIES) ) {
            return new JsonResponse([
                "error" => 'Currency not supported',
                "code" => 400
            ], Response::HTTP_BAD_REQUEST);
        }

        $url = 'http://api.nbp.pl/api/exchangerates/rates/c/' . $currency . '/' . $startDate . '/' . $endDate . '/';

        $response = $this->client->request(
            'GET',
            $url
        );

        $sumArray = [];
        $standDeviationArray = [];
        $array = $response->toArray();

        foreach ($array['rates'] as $item) {
            // BUY VALUE
            $sumArray[] = $item['bid'];

            // SELL VALUE
            $standDeviationArray[] = $item['ask'];
        }

        $averagePrice = $this->average($sumArray);
        $standDeviation = $this->standDeviation($standDeviationArray, $averagePrice);

        return new JsonResponse([
            "standard_deviation" => $standDeviation,
            "average_price" => $averagePrice
        ], Response::HTTP_OK);
    }

    /**
     * Calculates the mean of an array
     * 
     * @param array $arr
     * 
     * @return float
     */
    private function average(array $arr): float
    {
        if (empty($arr)) {
            return 0;
        }

        return array_sum($arr) / count($arr);
    }

    /**
     * Calculates the standard deviation of an array
     * 
     * @param array $arr
     * @param float $average
     * 
     * @return float
     */
    private function standDeviation(array $arr, float $average): float
    {
        if (empty($arr)) {
            return 0;
        }

        $elementsNumber = count($arr);
        $variance = 0.0;

        foreach($arr as $item)
        {
            // Sum of squares of differences between all numbers and means
            $variance += pow(($item - $average), 2);
        }

        return (float)sqrt($variance / $elementsNumber);
    }
}
