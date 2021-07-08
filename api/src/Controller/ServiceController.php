<?php

// src/Controller/ServiceController.php

namespace App\Controller;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
Use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ServiceController.
 *
 *
 * @Route("/service")
 */
class ServiceController extends AbstractController
{
    /**
     * This function routes a call from the UI to a specific commonground component
     *
     * @Route("/{service}/{endpoint}/{id}", methods={"GET","POST","PUT","UPDATE","DELETE"})
     * @Template
     */
    public function route(Request $request, CommonGroundService $commonGroundService, $service, $endpoint = '', $id = null)
    {
        /* @todo User validation */

        // Pass call parameters
        $query = $request->query->all();
        $header = $this->cleanHeaders($request->headers->all());
        $content = $request->getContent();
        $component = $commonGroundService->getComponent($service);

        if($component){

            if ($id) {
                $url = $commonGroundService->getUrlFromEndpoint(['component' => $service, 'type' => $endpoint, 'id' => $id], true);
            } else {
                $url = $commonGroundService->getUrlFromEndpoint(['component' => $service, 'type' => $endpoint], true);
            }

            // The service is a known component so lets handle the call
            $result = $commonGroundService->callService($component, $url, $request->getMethod(), $content, $query, $header);

            // Lets maps the service responce to a symfony responce
            $response = New Response();
            $response->setContent($result->getBody()->getContents());
            $response->headers->replace($result->getHeaders());
            $response->setStatusCode($result->getStatusCode());

        }
        else{
            throw new HttpException('404', 'Service not found');
        }

        // Throw unkon errot
        return $response;

    }

    public function cleanHeaders($headers)
    {
        unset($headers['content-type']);
        unset($headers['Authorization']);
        unset($headers['authorization']);
        unset($headers['Accept-Crs']);
        unset($headers['Content-Crs']);
        unset($headers['cookie']);
        unset($headers['content-length']);
        unset($headers['connection']);
        unset($headers['accept-encoding']);
        unset($headers['host']);
        unset($headers['postman-token']);
        unset($headers['user-agent']);
        unset($headers['x-php-ob-level']);
        unset($headers['accept']);
        return $headers;
    }


}
