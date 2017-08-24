<?php

namespace Geoks\AdminBundle\Controller\ApiDocs;

use Symfony\Component\HttpFoundation\Response;
use Geoks\AdminBundle\Controller\AdminPanelController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/admin-panel")
 */
class AdminPanelDoc extends AdminPanelController
{
    /**
     * @Route("/index", name="geoks_adminPanel_index")
     * @Method({"GET"})
     *
     * @return Response
     */
    public function indexAction()
    {
        return parent::indexAction();
    }

    /**
     * @Route("/logs", name="geoks_adminPanel_logs")
     * @Method({"GET"})
     *
     * @param Request $request
     * @return Response
     */
    public function logsAction(Request $request)
    {
        return parent::logsAction($request);
    }
}
