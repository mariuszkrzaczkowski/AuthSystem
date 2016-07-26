<?php
namespace Poirot\AuthSystem\Authenticate\Identifier;

use Poirot\AuthSystem\Authenticate\Exceptions\exNotAuthenticated;
use Poirot\AuthSystem\Authenticate\Identity\IdentityOpen;
use Poirot\AuthSystem\Authenticate\Interfaces\iIdentifier;
use Poirot\AuthSystem\Authenticate\Interfaces\iIdentity;

use Poirot\Std\ConfigurableSetter;

/**
 * Sign In/Out User as Identity into Environment(by session or something)
 *
 * - if identity is fulfilled/validated means user is recognized
 * - you can sign-in fulfillment identity
 * - sign-in/out take control of current identifier realm
 * - sign in some cases can be happen on request/response headers
 *
 */
abstract class aIdentifier
    extends ConfigurableSetter
    implements iIdentifier
{
    const DEFAULT_REALM          = 'Default_Auth';
    const STORAGE_IDENTITY_KEY   = 'identity';

    /** @var iIdentity */
    protected $identity;

    // options:
    /** @var iIdentity */
    protected $defaultIdentity;
    protected $realm;

    
    /**
     * Construct
     *
     * @param array|\Traversable $options
     */
    function __construct($realm = self::DEFAULT_REALM, $options = null)
    {
        parent::__construct($options);
        $this->setRealm($realm);
    }
    
    /**
     * Inject Identity
     *
     * @param iIdentity $identity
     *
     * @throws exNotAuthenticated Identity not full filled
     * @return $this
     */
    function setIdentity(iIdentity $identity)
    {
        $this->identity = $identity;
        return $this;
    }

    /**
     * Get Authenticated User Data
     *
     * - if identity exists use it
     * - otherwise if signIn extract data from it
     *   ie. when user exists in session build identity from that
     *
     * - not one of above situation return empty identity
     *
     * @return iIdentity
     */
    function identity()
    {
        if (!$this->identity)
            $this->identity = $this->_getDefaultIdentity();

        if($this->identity->isFulfilled())
            return $this->identity;


        // Attain Identity:
        if ($this->isSignIn()) {
            $identity = $this->doIdentifierSignedIdentity();
            if ($identity !== null)
                ## update identity
                $this->identity->import($identity);
        }

        return $this->identity;
    }


    /**
     * Attain Identity Object From Signed Sign
     * exp. session, extract from authorize header,
     *      load lazy data, etc.
     *
     * !! call when user is signed in to retrieve user identity
     *
     * note: almost retrieve identity data from cache or
     *       storage that store user data. ie. session
     *
     * @see identity()
     * @return iIdentity|\Traversable|null Null if no change need
     */
    abstract protected function doIdentifierSignedIdentity();


    // Options:

    /**
     * Set Realm To Limit Authentication
     *
     * ! mostly used as storage namespace to have
     *   multiple area for each different Authenticate system
     *
     * @param string $realm
     *
     * @return $this
     */
    function setRealm($realm)
    {
        $this->realm = (string) $realm;
        return $this;
    }

    /**
     * Get Realm Area
     *
     * @return string
     */
    function getRealm()
    {
        if (!$this->realm)
            $this->setRealm(self::DEFAULT_REALM);

        return $this->realm;
    }


    // Options:

    /**
     * Set Default Identity Instance
     * that Signed data load into
     *
     * @param iIdentity $identity
     * @return $this
     */
    function setDefaultIdentity(iIdentity $identity)
    {
        $this->defaultIdentity = $identity;
        return $this;
    }

    /**
     * Get Default Identity Instance
     * that Signed data load into
     *
     * @return iIdentity
     */
    protected function _getDefaultIdentity()
    {
        if (!$this->defaultIdentity)
            $this->defaultIdentity = new IdentityOpen;

        return $this->defaultIdentity;
    }
}