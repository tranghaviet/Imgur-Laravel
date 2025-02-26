<?php namespace Redeman\Imgur\Middleware;

use Closure;
use Imgur\Client;
use Redeman\Imgur\TokenStorage\Storage;
use Illuminate\Routing\Redirector;

class AuthenticateImgur {

    /**
     * The Imgur client
     * @var Client
     */
    protected $imgur;

    /**
     * Token storage
     * @var Storage
     */
    protected $store;


    /**
     * @var Redirector
     */
    protected $redirector;
    
    /**
     * @param Client $imgur
     * @param Storage $store
     * @param Redirector $redirector
     */
    public function __construct(Client $imgur, Storage $store, Redirector $redirector)
    {
        $this->imgur = $imgur;
        $this->store = $store;
        $this->redirector = $redirector;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Either authenticate the user if it's token is known,
        // request access to its token, or redirect the user
        // such that we can ask him to authorize our application
        if ($token = $this->store->get(config('imgur.storage_key'))) {
            $this->authenticateUser($token);
        } elseif ($code = $request->get('code')) {
            $this->requestAccess($code);
        } else {
            return $this->redirector->route('imgur.authenticate');
        }

        return $next($request);
    }

    /**
     * Authenticate the user with its token
     * @param  array $token
     * @return void
     */
    protected function authenticateUser($token)
    {
        $this->imgur->setAccessToken($token);
        // Refresh the token if necessary
        if($this->imgur->checkAccessTokenExpired())
        {
            $this->imgur->refreshToken();
        }
    }

    /**
     * Get the user's token by requesting access
     * @param  string $code the code returned by imgur
     * @return void
     */
    protected function requestAccess($code)
    {
        $this->imgur->requestAccessToken($code);

        // save the new token
        $token = $this->imgur->getAccessToken();
        $this->store->set(config('imgur.storage_key'), $token);

        // authenticate the user with the new token
        $this->authenticateUser($token);
    }
}
