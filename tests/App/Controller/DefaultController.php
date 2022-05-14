<?php

declare(strict_types=1);

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\Acme\Tests\App\Controller;

use Liip\Acme\Tests\App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;

class DefaultController extends AbstractController
{
    public function indexAction(): Response
    {
        return $this->render(
            'layout.html.twig'
        );
    }

    public function userAction(int $userId): Response
    {
        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('No user found');
        }

        return $this->render(
            'user.html.twig',
            ['user' => $user]
        );
    }

    public function formAction(Request $request): Response
    {
        return $this->form($request, 'form.html.twig');
    }

    public function formWithEmbedAction(Request $request): Response
    {
        return $this->form($request, 'form_with_embed.html.twig');
    }

    /**
     * Common form functionality used to test form submissions both
     * with and without an embedded request.
     */
    private function form(Request $request, string $template): Response
    {
        $defaultData = ['name' => null];

        $form = $this->createFormBuilder($defaultData)
            ->add('name', TextType::class, [
                'constraints' => new NotBlank(),
            ])
            ->add('Submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        $flashMessage = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $flashMessage = 'Name submitted.';
        }

        return $this->render(
            $template,
            [
                'form' => $form->createView(),
                'flash_message' => $flashMessage,
            ]
        );
    }

    /**
     * Used to test a JSON content with corresponding Content-Type.
     */
    public function jsonAction(): Response
    {
        $response = new Response(json_encode(['name' => 'John Doe']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Used to embed content as a sub-request.
     */
    public function embeddedAction(): Response
    {
        return new Response('Embedded Content', Response::HTTP_OK);
    }
}
