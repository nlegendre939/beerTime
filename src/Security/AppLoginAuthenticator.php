<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class AppLoginAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private $requestStack;
    private $urlGenerator;

    /* requestStack : récupérer session dans un service, urlGenerator : url de redirection */
    public function __construct(RequestStack $requestStack, UrlGeneratorInterface $urlGenerator)
    {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): ?bool
    {
        if($request->attributes->get('_route') !== 'user_login'){
            return false;
        }

        if($request->getMethod() !== 'POST'){
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_email');
        $password = $request->request->get('_password'); // $_POST['_password']
        $token = $request->request->get('_token');

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new RememberMeBadge(),
                new CsrfTokenBadge('authenticate', $token),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // $this->addFlash('notice', 'Vous êtes connecté'); ne fonctionne pas ici
        $session = $this->requestStack->getSession();
        $flashBag = $session->getFlashBag();
        $flashBag->add('notice', 'Vous êtes connecté');

        // return $this->redirectToRoute('main_index'); ne fonctionne pas ici
        $url = $this->urlGenerator->generate('main_index');
        return new RedirectResponse($url);
    }


    /* AuthenticationException : message d'erreur si erreur tentative de connexion */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $session = $this->requestStack->getSession();
        $session->set(Security::AUTHENTICATION_ERROR, $exception);

        return null;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
   {
        $session = $this->requestStack->getSession();
        $flashBag = $session->getFlashBag();
        $flashBag->add('info', 'Vous devez être connecté pour accéder à cette section');
        
        $url = $this->urlGenerator->generate('user_login');
        return new RedirectResponse($url);
   }
}