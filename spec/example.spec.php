<?php

describe("Example", function() {

    it("makes an expectation", function() {

         expect(true)->toBe(true);

    });

    // it("expects methods to be called", function() {

    //     $user = new User();
    //     expect($user)->toReceive('save')->with(['validates' => false]);
    //     $user->save(['validates' => false]);

    // });

    // it("stubs a function", function() {

    //     allow('time')->toBeCalled()->andReturn(123);
    //     $user = new User();
    //     expect($user->save())->toBe(true);
    //     expect($user->created)->toBe(123);

    // });

    // it("stubs a class", function() {

    //     allow('PDO')->toReceive('prepare', 'fetchAll')->andReturn([['name' => 'bob']]);
    //     $user = new User();
    //     expect($user->all())->toBe([['name' => 'bob']]);

    // });

});
