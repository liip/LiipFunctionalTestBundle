<?php

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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;

class DefaultController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render(
            'LiipFunctionalTestBundle::layout.html.twig'
        );
    }

    /**
     * @param int $userId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function userAction($userId)
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
            'LiipFunctionalTestBundle:Default:user.html.twig',
            array('user' => $user)
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function formAction(Request $request)
    {
        $object = new \ArrayObject();
        $object->name = null;

        $textType = method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix') ? 'Symfony\Component\Form\Extension\Core\Type\TextType' : 'text';
        $submitType = method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix') ? 'Symfony\Component\Form\Extension\Core\Type\SubmitType' : 'submit';

        $form = $this->createFormBuilder($object)
            ->add('name', $textType, array(
                /* @see http://symfony.com/doc/2.7/book/forms.html#adding-validation */
                'constraints' => new NotBlank(),
            ))
            ->add('Submit', $submitType)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('session')->getFlashBag()->add('notice',
                'Name submitted.'
            );
        }

        return $this->render(
            'LiipFunctionalTestBundle:Default:form.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * Used to test a JSON content with corresponding Content-Type.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function jsonAction()
    {
        $response = new Response(json_encode(array('name' => 'John Doe')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
