<?php

namespace App\Controller;

use App\Service\OpenURLSerice;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class DirectLinkController extends AbstractController
{
    private OpenURLSerice $openurl;

    public function __construct(OpenURLSerice $openurl)
    {
        $this->openurl = $openurl;
    }

    public function link(string $mms): RedirectResponse
    {
        $link = $this->openurl->lookup($mms);
        return $this->redirect($link);
    }
}
