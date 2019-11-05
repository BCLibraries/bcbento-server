<?php

namespace App\Controller;

use App\Service\ClientErrorLog\ClientErrorLog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LogController extends AbstractController
{
    /** @var ClientErrorLog */
    private $logger;

    public function __construct(ClientErrorLog $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/log", methods={"POST"})
     */
    public function logError(Request $request): Response
    {
        $this->logger->add($request->getContent(), $request->getClientIp());
        return new Response('', 200);
    }
}
