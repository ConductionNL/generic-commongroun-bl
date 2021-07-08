<?php

// src/Controller/ServiceController.php

namespace App\Controller;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
Use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class ServiceController.
 *
 *
 * @Route("/service")
 */
class ServiceController extends AbstractController
{

    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * This function routes a call from the UI to a specific commonground component
     *
     * @Route("/{service}/{endpoint}/{id}", methods={"GET","POST","PUT","UPDATE","DELETE"})
     * @Template
     */
    public function route(Request $request, CommonGroundService $commonGroundService, $service, $endpoint = '', $id = null)
    {
        /* @todo User validation */

        //user validation
        $auth = $request->headers->get('Authorization');
        $this->checkUser($auth);

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
            $result = $commonGroundService->callService($component, $url, $content, $query, $header, false, $request->getMethod());

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

    public function checkUser($auth) {
        if (strpos($auth, 'Bearer') !== false) {
            $token = str_replace('Bearer ', '', $auth);
            $json = base64_decode(explode('.', $token)[1]);
            $json = json_decode($json, true);

            if (!isset($json['userId'])) {
                throw new HttpException('403', 'Access Denied');
            }

            $item = $this->cache->getItem('code_'.md5($json['userId']));

            if (!$item->isHit()) {
                throw new HttpException('403', 'Access Denied');
            }
        } else {
            throw new HttpException('403', 'Access Denied');
        }
    }


}
