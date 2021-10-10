<?php

namespace AnthonyConklin\LaravelTasks\Responses;

trait RespondWith
{
    /**
     * @param null $content
     *
     * @return Response
     */
    public function respondWith($content = null)
    {
        return (new Response())->setContent($content);
    }
}
