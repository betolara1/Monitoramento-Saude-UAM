<?php

/**
 * This code was generated by
 * ___ _ _ _ _ _    _ ____    ____ ____ _    ____ ____ _  _ ____ ____ ____ ___ __   __
 *  |  | | | | |    | |  | __ |  | |__| | __ | __ |___ |\ | |___ |__/ |__|  | |  | |__/
 *  |  |_|_| | |___ | |__|    |__| |  | |    |__] |___ | \| |___ |  \ |  |  | |__| |  \
 *
 * Twilio - Voice
 * This is the public Twilio REST API.
 *
 * NOTE: This class is auto generated by OpenAPI Generator.
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */


namespace Twilio\Rest\Voice\V1\ConnectionPolicy;

use Twilio\Exceptions\TwilioException;
use Twilio\InstanceResource;
use Twilio\Options;
use Twilio\Values;
use Twilio\Version;
use Twilio\Deserialize;


/**
 * @property string|null $accountSid
 * @property string|null $connectionPolicySid
 * @property string|null $sid
 * @property string|null $friendlyName
 * @property string|null $target
 * @property int $priority
 * @property int $weight
 * @property bool|null $enabled
 * @property \DateTime|null $dateCreated
 * @property \DateTime|null $dateUpdated
 * @property string|null $url
 */
class ConnectionPolicyTargetInstance extends InstanceResource
{
    /**
     * Initialize the ConnectionPolicyTargetInstance
     *
     * @param Version $version Version that contains the resource
     * @param mixed[] $payload The response payload
     * @param string $connectionPolicySid The SID of the Connection Policy that owns the Target.
     * @param string $sid The unique string that we created to identify the Target resource to delete.
     */
    public function __construct(Version $version, array $payload, string $connectionPolicySid, string $sid = null)
    {
        parent::__construct($version);

        // Marshaled Properties
        $this->properties = [
            'accountSid' => Values::array_get($payload, 'account_sid'),
            'connectionPolicySid' => Values::array_get($payload, 'connection_policy_sid'),
            'sid' => Values::array_get($payload, 'sid'),
            'friendlyName' => Values::array_get($payload, 'friendly_name'),
            'target' => Values::array_get($payload, 'target'),
            'priority' => Values::array_get($payload, 'priority'),
            'weight' => Values::array_get($payload, 'weight'),
            'enabled' => Values::array_get($payload, 'enabled'),
            'dateCreated' => Deserialize::dateTime(Values::array_get($payload, 'date_created')),
            'dateUpdated' => Deserialize::dateTime(Values::array_get($payload, 'date_updated')),
            'url' => Values::array_get($payload, 'url'),
        ];

        $this->solution = ['connectionPolicySid' => $connectionPolicySid, 'sid' => $sid ?: $this->properties['sid'], ];
    }

    /**
     * Generate an instance context for the instance, the context is capable of
     * performing various actions.  All instance actions are proxied to the context
     *
     * @return ConnectionPolicyTargetContext Context for this ConnectionPolicyTargetInstance
     */
    protected function proxy(): ConnectionPolicyTargetContext
    {
        if (!$this->context) {
            $this->context = new ConnectionPolicyTargetContext(
                $this->version,
                $this->solution['connectionPolicySid'],
                $this->solution['sid']
            );
        }

        return $this->context;
    }

    /**
     * Delete the ConnectionPolicyTargetInstance
     *
     * @return bool True if delete succeeds, false otherwise
     * @throws TwilioException When an HTTP error occurs.
     */
    public function delete(): bool
    {

        return $this->proxy()->delete();
    }

    /**
     * Fetch the ConnectionPolicyTargetInstance
     *
     * @return ConnectionPolicyTargetInstance Fetched ConnectionPolicyTargetInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function fetch(): ConnectionPolicyTargetInstance
    {

        return $this->proxy()->fetch();
    }

    /**
     * Update the ConnectionPolicyTargetInstance
     *
     * @param array|Options $options Optional Arguments
     * @return ConnectionPolicyTargetInstance Updated ConnectionPolicyTargetInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function update(array $options = []): ConnectionPolicyTargetInstance
    {

        return $this->proxy()->update($options);
    }

    /**
     * Magic getter to access properties
     *
     * @param string $name Property to access
     * @return mixed The requested property
     * @throws TwilioException For unknown properties
     */
    public function __get(string $name)
    {
        if (\array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }

        if (\property_exists($this, '_' . $name)) {
            $method = 'get' . \ucfirst($name);
            return $this->$method();
        }

        throw new TwilioException('Unknown property: ' . $name);
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string
    {
        $context = [];
        foreach ($this->solution as $key => $value) {
            $context[] = "$key=$value";
        }
        return '[Twilio.Voice.V1.ConnectionPolicyTargetInstance ' . \implode(' ', $context) . ']';
    }
}

