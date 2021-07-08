<?php

// src/Controller/USerController.php

namespace App\Controller;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
use Doctrine\ORM\Query\Parameter;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS512;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class UserController.
 */
class UserController extends AbstractController
{
    private ParameterBagInterface $params;
    private CacheInterface $cache;

    public function __construct(ParameterBagInterface $params, CacheInterface $cache)
    {
        $this->params = $params;
        $this->cache = $cache;
    }

    /**
     * This function checks to find the user and generates jwt to allow authentication
     *
     * @Route("/login", methods={"POST"})
     */
    public function login(Request $request, CommonGroundService $commonGroundService)
    {

        $content = json_decode($request->getContent(), true);

        try {
            $user = $commonGroundService->createResource($content, ['component' => 'uc', 'type' => 'login']);
        } catch(Exception $e) {
            throw new Exception('Invalid user credentials', '403');
        }

        $time = new DateTime();
        $expiry = new DateTime('+10 days');

        $jwtBody = [
            'userId' => $user['id'],
            'type'   => 'login',
            'iss'    => $this->params->get('app_name'),
            'ias'    => $time->getTimestamp(),
            'exp'    => $expiry->getTimestamp(),
        ];

        $item = $this->cache->getItem('code_'.md5($user['id']));
        $item->set($user);
        $this->cache->save($item);

        $jwt = $this->createJWTToken($jwtBody);

        return new Response(
            json_encode($jwt),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );

    }

    /**
     * Creates a RS512-signed JWT token for a provided payload.
     *
     * @param array $payload The payload to encode
     *
     * @return string The resulting JWT token
     */
    public function createJWTToken(array $payload): string
    {
        $algorithmManager = new AlgorithmManager([new RS512()]);
        $pem = $this->writeFile(base64_decode($this->params->get('private_key')), 'pem');
        $jwk = JWKFactory::createFromKeyFile($pem);
        $this->removeFiles([$pem]);

        $jwsBuilder = new JWSBuilder($algorithmManager);
        $jws = $jwsBuilder
            ->create()
            ->withPayload(json_encode($payload))
            ->addSignature($jwk, ['alg' => 'RS512'])
            ->build();

        $serializer = new CompactSerializer();

        return $serializer->serialize($jws, 0);
    }

    /**
     * Writes a temporary file in the component file system.
     *
     * @param string $contents The contents of the file to write
     * @param string $type     The type of file to write
     *
     * @return string The location of the written file
     */
    public function writeFile(string $contents, string $type): string
    {
        $stamp = microtime().getmypid();
        file_put_contents(dirname(__FILE__, 3).'/var/'.$type.'-'.$stamp, $contents);

        return dirname(__FILE__, 3).'/var/'.$type.'-'.$stamp;
    }

    /**
     * Removes (temporary) files from the filesystem.
     *
     * @param array $files An array of file paths of files to delete
     */
    public function removeFiles(array $files): void
    {
        foreach ($files as $filename) {
            unlink($filename);
        }
    }


}
