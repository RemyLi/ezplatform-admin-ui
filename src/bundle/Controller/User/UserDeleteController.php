<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\EzPlatformAdminUiBundle\Controller\User;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use EzSystems\EzPlatformAdminUi\Form\Data\User\UserDeleteData;
use EzSystems\EzPlatformAdminUi\Form\Factory\FormFactory;
use EzSystems\EzPlatformAdminUi\Form\SubmitHandler;
use EzSystems\EzPlatformAdminUi\Notification\NotificationHandlerInterface;
use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\TranslatorInterface;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\LocationService;

class UserDeleteController extends Controller
{
    /** @var NotificationHandlerInterface */
    private $notificationHandler;

    /** @var FormFactory */
    private $formFactory;

    /** @var SubmitHandler */
    private $submitHandler;

    /** @var TranslatorInterface */
    private $translator;

    /** @var UserService */
    private $userService;

    /** @var LocationService */
    private $locationService;

    /**
     * @param NotificationHandlerInterface $notificationHandler
     * @param FormFactory $formFactory
     * @param SubmitHandler $submitHandler
     * @param TranslatorInterface $translator
     * @param UserService $userService
     * @param LocationService $locationService
     */
    public function __construct(
        NotificationHandlerInterface $notificationHandler,
        FormFactory $formFactory,
        SubmitHandler $submitHandler,
        TranslatorInterface $translator,
        UserService $userService,
        LocationService $locationService
    ) {
        $this->notificationHandler = $notificationHandler;
        $this->formFactory = $formFactory;
        $this->submitHandler = $submitHandler;
        $this->translator = $translator;
        $this->userService = $userService;
        $this->locationService = $locationService;
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     * @throws InvalidArgumentException
     * @throws UnauthorizedException
     * @throws NotFoundException
     */
    public function userDeleteAction(Request $request): Response
    {
        $form = $this->formFactory->deleteUser();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $result = $this->submitHandler->handle($form, function (UserDeleteData $data) {
                $contentInfo = $data->getContentInfo();

                $location = $this->locationService->loadLocation($contentInfo->mainLocationId);

                $user = $this->userService->loadUser($contentInfo->id);

                $this->userService->deleteUser($user);

                $this->notificationHandler->success(
                    $this->translator->trans(
                        /** @Desc("User with login '%login%' deleted.") */
                        'user.delete.success',
                        ['%login%' => $user->login],
                        'content'
                    )
                );

                return new RedirectResponse($this->generateUrl('_ezpublishLocation', [
                    'locationId' => $location->parentLocationId,
                ]));
            });

            if ($result instanceof Response) {
                return $result;
            }
        }

        return $this->redirect($this->generateUrl('ezplatform.dashboard'));
    }
}
