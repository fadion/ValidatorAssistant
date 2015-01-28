<?php

use Mockery as m;

class ValidatorAssistantTest extends PHPUnit_Framework_TestCase {

    private $inputs = array('name' => 'Fadion');
    
    public function tearDown()
    {
        m::close();
    }
    
    public function testValidationPassed()
    {
        $response = m::mock('StdClass');
        $response->shouldReceive('passes', 'setAttributeNames')->once()->andReturn(true);
        $response->shouldReceive('messages')->once();

        $validator = m::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('make')->once()->andReturn($response);
        $validator->shouldReceive('extend')->zeroOrMoreTimes();

        $testValidator = new TestValidator($this->inputs, $validator);
        
        $this->assertTrue($testValidator->passes());
        $this->assertNull($testValidator->errors());
    }
    
    public function testValidationFailed()
    {
        $response = m::mock('StdClass');
        $response->shouldReceive('fails', 'setAttributeNames')->once()->andReturn(true);
        $response->shouldReceive('messages')->once()->andReturn('some message');
        $response->shouldReceive('failed')->once()->andReturn(array('failed', 'rules'));

        $validator = m::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('make')->once()->andReturn($response);
        $validator->shouldReceive('extend')->zeroOrMoreTimes();

        $testValidator = new TestValidator($this->inputs, $validator);

        $this->assertTrue($testValidator->fails());
        $this->assertNotNull($testValidator->errors());
        $this->assertNotEmpty($testValidator->failed());
    }

    public function testReturnValidatorInstance()
    {
        $validator = m::mock('Illuminate\Validation\Validator');
        $testValidator = new TestValidator($this->inputs, $validator);

        $this->assertEquals($validator, $testValidator->instance());
    }

    public function testAddRule()
    {
        $validator = m::mock('Illuminate\Validation\Validator');
        $testValidator = new TestValidator($this->inputs, $validator);

        $testValidator->addRule('age', 'required');
        $rules = $testValidator->getRules();

        $this->assertArrayHasKey('age', $rules);
        $this->assertEquals('required', $rules['age']);
    }

    public function testAddMessage()
    {
        $validator = m::mock('Illuminate\Validation\Validator');
        $testValidator = new TestValidator($this->inputs, $validator);

        $testValidator->addMessage('age', 'Age is required');
        $messages = $testValidator->getMessages();

        $this->assertArrayHasKey('age', $messages);
        $this->assertEquals('Age is required', $messages['age']);
    }
    
    public function testAppendRule()
    {
        $validator = m::mock('Illuminate\Validation\Validator');
        $testValidator = new TestValidator($this->inputs, $validator);

        $testValidator->append('name', 'alpha');
        $testValidator->append('email', 'alpha_num');
        $rules = $testValidator->getRules();

        $this->assertEquals('required|alpha', $rules['name']);
        $this->assertEquals('required|email|alpha_num', $rules['email']);
    }

    public function testBindRule()
    {
        $validator = m::mock('Illuminate\Validation\Validator');
        $testValidator = new TestValidator($this->inputs, $validator);

        $testValidator->bind('date', '1985-02-05');
        $testValidator->bindTable('users');
        $rules = $testValidator->getRules();

        $this->assertEquals('before:1985-02-05', $rules['birthday']);
        $this->assertEquals('unique:users', $rules['username']);
    }

    public function testFilters()
    {
        $response = m::mock('StdClass');
        $response->shouldReceive('passes', 'setAttributeNames')->once()->andReturn(true);

        $validator = m::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('make')->once()->andReturn($response);
        $validator->shouldReceive('extend')->zeroOrMoreTimes();

        $testValidator = new TestValidator($this->inputs, $validator);
        $testValidator->passes();
        $rules = $testValidator->getInputs();

        $this->assertEquals(strtoupper($this->inputs['name']), $rules['name']);
    }
    
    public function testScope()
    {
        $validator = m::mock('Illuminate\Validation\Validator');
        $testValidator = new TestValidator($this->inputs, $validator);
        
        $rules = array_merge($testValidator->getRules(), array('bio' => 'required'));
        $testValidator->scope('profile');
        
        $this->assertEquals($rules, $testValidator->getRules());
    }
    
    public function testSubRules()
    {
        $response = m::mock('StdClass');
        $response->shouldReceive('passes', 'setAttributeNames')->once()->andReturn(true);

        $validator = m::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('make')->once()->andReturn($response);
        $validator->shouldReceive('extend')->zeroOrMoreTimes();

        $inputs = array(
            'title' => array('sq' => 'Albanian', 'en' => 'English')
        );

        $testValidator = new TestValidator($inputs, $validator);
        $testValidator->passes();
        
        $rules = $testValidator->getRules();
        $messages = $testValidator->getMessages();
        
        $this->assertArrayHasKey('title_sq', $rules);
        $this->assertArrayHasKey('title_en', $rules);
        
        $this->assertArrayHasKey('title_sq.required', $messages);
        $this->assertArrayHasKey('title_en.required', $messages);
    }

    public function testCustomRules()
    {
        $response = m::mock('StdClass');
        $response->shouldReceive('passes', 'setAttributeNames')->once()->andReturn(true);
        $response->shouldReceive('messages')->once();

        $validator = m::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('make')->once()->andReturn($response);
        $validator->shouldReceive('extend')->zeroOrMoreTimes();

        $inputs['bar'] = 'foobar';

        $testValidator = new TestValidator($inputs, $validator);

        $this->assertTrue($testValidator->passes());
        $this->assertNull($testValidator->errors());
    }

    public function testBeforeEvent()
    {
        $validator = m::mock('Illuminate\Validation\Validator');
        $testValidator = new TestValidator($this->inputs, $validator);
        $rules = $testValidator->getRules();

        $this->assertNotNull($rules['event']);
        $this->assertEquals('before', $rules['event']);
    }

    public function testAfterEvent()
    {
        $response = m::mock('StdClass');
        $response->shouldReceive('passes', 'setAttributeNames')->once()->andReturn(true);

        $validator = m::mock('Illuminate\Validation\Validator');
        $validator->shouldReceive('make')->once()->andReturn($response);
        $validator->shouldReceive('extend')->zeroOrMoreTimes();

        $testValidator = new TestValidator($this->inputs, $validator);
        $testValidator->passes();
        $rules = $testValidator->getRules();

        $this->assertNotNull($rules['event']);
        $this->assertEquals('after', $rules['event']);
    }
    
}

class TestValidator extends \Fadion\ValidatorAssistant\ValidatorAssistant {
    
    protected $rules = array(
        'name' => 'required',
        'email' => 'required|email',
        'birthday' => 'before:{date}',
        'username' => 'unique:{table}',
        'title[sq]' => 'required',
        'title[en]' => 'required',
        'bar' => 'foo',
        'event' => null
    );
    
    protected $rulesProfile = array(
        'bio' => 'required'
    );
    
    protected $filters = array(
        'name' => 'upper'
    );
    
    protected $messages = array(
        'name.required' => 'Name is required',
        'email.required' => 'Email is required',
        'email.email' => 'Email is invalid',
        'title[sq].required' => 'Albanian title is required',
        'title[en].required' => 'English title is required'
    );

    protected function customFoo($attribute, $value, $parameters)
    {
        return $value == 'foobar';
    }

    protected function before()
    {
        $this->rules['event'] = 'before';
    }

    protected function after()
    {
        $this->rules['event'] = 'after';
    }
    
}