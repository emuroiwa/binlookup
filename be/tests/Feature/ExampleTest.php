<?php

it("returns a successful response for api health check", function () {
    $response = $this->getJson("/api/bin-data/filter-options");

    $response->assertStatus(200);
});
