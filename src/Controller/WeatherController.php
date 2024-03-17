<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    #[Route('/', name: 'app_weather')]
    public function index(): Response
    {
        return $this->render('weather/index.html.twig');
    }

    #[Route('/search', name: 'app_weather_search')]
    public function search(Request $request): Response
    {
        $accuWeatherApiKey = $this->getParameter('app.accuweather_api_key');
        $search = $request->get('city');
        $location = $this->client->request(
            'GET',
            'http://dataservice.accuweather.com/locations/v1/cities/search',
            [
                'query' => [
                    'apikey' => $accuWeatherApiKey,
                    'q' => $search
                ]
            ]
        );

        if ($location->getStatusCode() !== 200) {
            // Return error
        }

        $currentCondition = $this->client->request(
            'GET',
            'http://dataservice.accuweather.com/currentconditions/v1/' . $location->toArray()[0]['Key'],
            [
                'query' => [
                    'apikey' => $accuWeatherApiKey
                ]
            ]
        );

        if ($currentCondition->getStatusCode() !== 200) {
            // Return error
        }

        $isNight = false;
        $time = date('H', strtotime($currentCondition->toArray()[0]['LocalObservationDateTime']));
        if ($time < 8 && $time > 20) {
            $isNight = true;
        }

        $weather = [
            'location' => $location->toArray()[0]['LocalizedName'],
            'country' => $location->toArray()[0]['Country']['LocalizedName'],
            'localObservationDateTime' => $currentCondition->toArray()[0]['LocalObservationDateTime'],
            'weatherText' => $currentCondition->toArray()[0]['WeatherText'],
            'temperature' => $currentCondition->toArray()[0]['Temperature']['Metric']['Value'] . '°' . $currentCondition->toArray()[0]['Temperature']['Metric']['Unit'],
            'icon' => $currentCondition->toArray()[0]['WeatherIcon'],
        ];

        return $this->render('weather/_card.html.twig', [
            'weather' => $weather,
            'isNight' => $isNight,
        ]);
    }
}
