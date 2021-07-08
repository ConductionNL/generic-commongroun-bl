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
     * @Route("/{service}/{endpoint}", methods={"GET","POST","UPDATE","DELTE"})
     * @Template
     */
    public function route(Request $request, CommonGroundService $commonGroundService, $service, $endpoint = '')
    {
        /* @todo User validation */
        

        // Define the service
        $service = ['component'=> $service, 'endpoint'=> $endpoint];

        // Pass call parameters
        $query = $request->query->all();
        $header = $request->headers->all();
        $content = $request->getContent();

        $component = $commonGroundService->getComponent($service);

        if($component){
            // The service is a known component so lets handle the call
            $result = $commonGroundService->callService($service, $request->getMethod(), $content, $query, $header);

            // Lets maps the service responce to a symfony responce
            $response = New Response();
            $response->sendContent($result->getBody()->getContents());
            $response->sendHeaders($result->getHeaders());
            $response->setStatusCode($result->getStatusCode());

        }
        else{
            // Throw unknown service error
        }

        // Throw unkon errot
        return $response;

    }


}
