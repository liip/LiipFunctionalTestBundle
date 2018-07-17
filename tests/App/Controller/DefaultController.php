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

namespace Liip\FunctionalTestBundle\Tests\App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;

class DefaultController extends Controller
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
        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user */
        $user = $this->getDoctrine()
            ->getRepository('LiipFunctionalTestBundle:User')
            ->find($userId);

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found'
            );
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
            'form.html.twig',
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
}
