<?php

namespace Illuminate\Tests\Integration\Database\EloquentRelationshipThroughBuilder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group f
 */
class EloquentRelationshipThroughBuilder extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
        });

        Schema::create('posts', function ($table) {
            $table->increments('id');
            $table->string('user_email');
            $table->string('text');
        });
    }

    public function test_relationship_definition_with_an_eloquent_builder()
    {
        $user = User::create([
            'email' => $email = 'framework@laravel.com'
        ]);

        $post = Post::create([
            'user_email' => $email,
            'text' => 'This is a post.'
        ]);

        $this->assertInstanceOf(Collection::class, $user->posts);

        $this->assertEquals($post->toArray(), $user->posts->first()->toArray());
    }

    public function test_eager_loading_through_eloquent_builder_relationship()
    {
        User::create([
            'email' => $email = 'framework@laravel.com'
        ]);

        Post::create([
            'user_email' => $email,
            'text' => 'This is a post.'
        ]);

        DB::enableQueryLog();

        $user = User::with('posts')->first();

        $this->assertInstanceOf(Collection::class, $user->posts);
        $this->assertSame($user->posts->first()->user_email, $email);
        $this->assertSame($user->posts->first()->text, 'This is a post.');

        DB::disableQueryLog();

        $this->assertSame(2, count(DB::getQueryLog()));
    }

    public function test_count_eager_loading_through_eloquent_builder_relationship()
    {
        User::create([
            'email' => $email = 'framework@laravel.com'
        ]);

        Post::create([
            'user_email' => $email,
            'text' => 'This is a post.'
        ]);

        Post::create([
            'user_email' => $email,
            'text' => 'This is another post.'
        ]);

        $user = User::withCount('posts')->first();

        $this->assertSame(2, $user->posts_count);
    }

    public function test_relationship_definition_with_a_query_builder()
    {
        $user = User::create([
            'email' => $email = 'framework@laravel.com'
        ]);

        $post = Post::create([
            'user_email' => $email,
            'text' => 'This is a post.'
        ]);

        $this->assertEquals($user->email, $post->user->first()->email);
    }
}


class User extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function posts()
    {
        return Post::query()
            ->where('user_email', $this->email);
    }
}


class Post extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function user()
    {
        return DB::table('users')->where('email', $this->user_email);
    }
}
