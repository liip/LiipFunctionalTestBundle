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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;

class DefaultController extends AbstractController
{
    /**
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render(
            'layout.html.twig'
        );
    }

    /**
     * @param int $userId
     *
     * @return Response
     */
    public function userAction(int $userId): Response
    {
        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        $user = $this->getDoctrine()
            ->getRepository('LiipFunctionalTestBundle:User')
            ->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('No user found');
        }

        return $this->render(
            'user.html.twig',
            ['user' => $user]
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function formAction(Request $request): Response
    {
        return $this->form($request, 'form.html.twig');
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function formWithEmbedAction(Request $request): Response
    {
        return $this->form($request, 'form_with_embed.html.twig');
    }

    /**
     * Common form functionality used to test form submissions both
     * with and without an embedded request.
     *
     * @param Request $request
     * @param string  $template
     *
     * @return Response
     */
    private function form(Request $request, string $template): Response
    {
        $object = new \ArrayObject();
        $object->name = null;

        $form = $this->createFormBuilder($object)
            ->add('name', TextType::class, [
                /* @see http://symfony.com/doc/2.7/book/forms.html#adding-validation */
                'constraints' => new NotBlank(),
            ])
            ->add('Submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('session')->getFlashBag()->add('notice',
                'Name submitted.'
            );
        }

        return $this->render(
            $template,
            ['form' => $form->createView()]
        );
    }

    /**
     * Used to test a JSON content with corresponding Content-Type.
     *
     * @return Response
     */
    public function jsonAction(): Response
    {
        $response = new Response(json_encode(['name' => 'John Doe']));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Used to embed content as a sub-request.
     *
     * @return Response
     */
    public function embeddedAction(): Response
    {
        return new Response('Embedded Content', Response::HTTP_OK);
    }
}
