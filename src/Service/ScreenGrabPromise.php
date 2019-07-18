<?php


namespace App\Service;

use GuzzleHttp\Promise\PromiseInterface;

class ScreenGrabPromise implements PromiseInterface
{

    /**
     * Appends fulfillment and rejection handlers to the promise, and returns
     * a new promise resolving to the return value of the called handler.
     *
     * @param callable $onFulfilled Invoked when the promise fulfills.
     * @param callable $onRejected Invoked when the promise is rejected.
     *
     * @return PromiseInterface
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null)
    {
        // TODO: Implement then() method.
    }

    /**
     * Appends a rejection handler callback to the promise, and returns a new
     * promise resolving to the return value of the callback if it is called,
     * or to its original fulfillment value if the promise is instead
     * fulfilled.
     *
     * @param callable $onRejected Invoked when the promise is rejected.
     *
     * @return PromiseInterface
     */
    public function otherwise(callable $onRejected)
    {
        // TODO: Implement otherwise() method.
    }

    /**
     * Get the state of the promise ("pending", "rejected", or "fulfilled").
     *
     * The three states can be checked against the constants defined on
     * PromiseInterface: PENDING, FULFILLED, and REJECTED.
     *
     * @return string
     */
    public function getState()
    {
        // TODO: Implement getState() method.
    }

    /**
     * Resolve the promise with the given value.
     *
     * @param mixed $value
     * @throws \RuntimeException if the promise is already resolved.
     */
    public function resolve($value)
    {
        // TODO: Implement resolve() method.
    }

    /**
     * Reject the promise with the given reason.
     *
     * @param mixed $reason
     * @throws \RuntimeException if the promise is already resolved.
     */
    public function reject($reason)
    {
        // TODO: Implement reject() method.
    }

    /**
     * Cancels the promise if possible.
     *
     * @link https://github.com/promises-aplus/cancellation-spec/issues/7
     */
    public function cancel()
    {
        // TODO: Implement cancel() method.
    }

    /**
     * Waits until the promise completes if possible.
     *
     * Pass $unwrap as true to unwrap the result of the promise, either
     * returning the resolved value or throwing the rejected exception.
     *
     * If the promise cannot be waited on, then the promise will be rejected.
     *
     * @param bool $unwrap
     *
     * @return mixed
     * @throws \LogicException if the promise has no wait function or if the
     *                         promise does not settle after waiting.
     */
    public function wait($unwrap = true)
    {
        // TODO: Implement wait() method.
    }
}