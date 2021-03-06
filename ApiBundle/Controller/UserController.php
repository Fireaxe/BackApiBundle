<?php

namespace Geoks\ApiBundle\Controller;

use Geoks\ApiBundle\Form\Security\ChangePasswordForm;
use Geoks\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserController
 * @package Geoks\ApiBundle\Controller
 *
 * Default CRUD user
 */
abstract class UserController extends ApiController
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $userRepository = "AppBundle\\Entity\\User";

    /**
     * @var string
     */
    private $formCreate = "Geoks\\ApiBundle\\Form\\Basic\\CreateForm";

    /**
     * @var string
     */
    private $formUpdate = "Geoks\\ApiBundle\\Form\\Basic\\UpdateForm";

    /**
     * @return string
     */
    protected function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    protected function getUserRepository()
    {
        return $this->userRepository;
    }

    /**
     * @return string
     */
    protected function getFormCreate()
    {
        return $this->formCreate;
    }

    /**
     * @return string
     */
    protected function getFormUpdate()
    {
        return $this->formUpdate;
    }

    /**
     * AdminController constructor.
     *
     * @param null|string $userRepository
     * @param null|string $formCreate
     * @param null|string $formUpdate
     */
    public function __construct($userRepository = null, $formCreate = null, $formUpdate = null)
    {
        // Entity Naming
        if ($userRepository) {
            $this->userRepository = $userRepository;
        }

        $this->className = (new \ReflectionClass($this->userRepository))->getShortName();

        // Forms
        if ($formCreate) {
            $this->formCreate = $formCreate;
        }

        if ($formUpdate) {
            $this->formUpdate = $formUpdate;
        }
    }

    public function getAllAction()
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->serializeResponse("geoks.user.forbidden", Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository($this->userRepository)->findBy(array('enabled' => true));

        return $this->serializeResponse(['list' => $users]);
    }

    public function meAction()
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->userRepository)->findOneBy(array('username' => $this->getUser()->getUsername()));

        if (!$user) {
            return $this->serializeResponse("geoks.user.notFound", Response::HTTP_NOT_FOUND);
        }

        if ($user != $this->getUser()) {
            return $this->serializeResponse("geoks.user.forbidden", Response::HTTP_FORBIDDEN);
        }

        return $this->serializeResponse(['details' => $user]);
    }

    public function meCustomAction($group)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->userRepository)->findOneBy(array('username' => $this->getUser()->getUsername()));

        if (!$user) {
            return $this->serializeResponse("geoks.user.notFound", Response::HTTP_NOT_FOUND);
        }

        if ($user != $this->getUser()) {
            return $this->serializeResponse("geoks.user.forbidden", Response::HTTP_FORBIDDEN);
        }

        return $this->serializeResponse([$group => $user]);
    }

    public function getOneAction($id)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->userRepository)->find($id);

        if (!$user) {
            return $this->serializeResponse("geoks.user.notFound", Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse(['details' => $user]);
    }

    public function getOneCustomAction($id, $group)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->userRepository)->find($id);

        if (!$user) {
            return $this->serializeResponse("geoks.user.notFound", Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse([$group => $user]);
    }

    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->getUserRepository())->find($id);

        if (!$user) {
            return $this->serializeResponse('geoks.user.notFound', Response::HTTP_NOT_FOUND);
        }

        if ($this->getUser() != $user) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $form = $this->createForm($this->getFormUpdate(), $user, [
            'method' => $request->getMethod(),
            'data_class' => $this->getUserRepository(),
            'service_container' => $this->get('service_container'),
            'change_password' => false
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em->flush();

            return $this->serializeResponse(['details' => $user]);
        }

        return $this->serializeResponse($form, Response::HTTP_BAD_REQUEST);
    }

    public function deleteAction($id = null)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();

        if (!$id) {
            $user = $em->getRepository($this->userRepository)->find($this->getUser()->getId());
        } else {
            $user = $em->getRepository($this->userRepository)->find($id);
        }

        if (!$user) {
            return $this->serializeResponse("geoks.user.notFound", Response::HTTP_NOT_FOUND);
        }

        if ($this->getUser() != $user) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                return $this->serializeResponse("geoks.user.forbidden", Response::HTTP_FORBIDDEN);
            }
        }

        $em->remove($user);
        $em->flush();

        return $this->serializeResponse("geoks.user.deleted");
    }

    public function disabledAction($id = null)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();

        if (!$id) {
            /** @var User $user */
            $user = $em->getRepository($this->userRepository)->find($this->getUser()->getId());
        } else {
            $user = $em->getRepository($this->userRepository)->find($id);
        }

        if (!$user) {
            return $this->serializeResponse("geoks.user.notFound", Response::HTTP_NOT_FOUND);
        }

        if ($this->getUser() != $user) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                return $this->serializeResponse("geoks.user.forbidden", Response::HTTP_FORBIDDEN);
            }
        }

        $user->setEnabled(false);
        $em->flush();

        return $this->serializeResponse("geoks.user.disabled");
    }

    public function changeUserPasswordAction(Request $request)
    {
        $user = $this->getUser();
        $userManager = $this->container->get('fos_user.user_manager.default');

        $form = $this->createForm(ChangePasswordForm::class);
        $form->handleRequest($request);

        if (!$form->get('current_password')->getData() && !$form->get('currentPassword')->getData()) {
            return $this->serializeResponse($this->get('translator')->trans('geoks.user.no_password'), Response::HTTP_BAD_REQUEST);
        }

        if (!($this->checkUserPassword($user, $form->get('current_password')->getData()) || $this->checkUserPassword($user, $form->get('currentPassword')->getData()))) {
            return $this->serializeResponse($this->get('translator')->trans('geoks.user.password.wrong'), Response::HTTP_BAD_REQUEST);
        }

        if ($form->isValid()) {
            $user->setPlainPassword($form->get('new')->getData());
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);

            $userManager->updateUser($user);

            return $this->serializeResponse(["details" => $user]);
        }

        return $this->serializeResponse($form, Response::HTTP_BAD_REQUEST);
    }

    public function updateUserPictureAction(Request $request)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse(["error" => $this->get('translator')->trans("geoks.user.notConnected")], Response::HTTP_FORBIDDEN);
        }

        if (!$request->files->get('imageFile')) {
            return $this->serializeResponse(["error" => $this->get('translator')->trans("geoks.imageFile.notFound")], Response::HTTP_BAD_REQUEST);
        }

        /** @var File $image */
        $image = $request->files->get('imageFile');

        $this->getUser()->setImageFile(new UploadedFile($image, $image->getFilename()));

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->serializeResponse(["details" => $this->getUser()]);
    }
}
