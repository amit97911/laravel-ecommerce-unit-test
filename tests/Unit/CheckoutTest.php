<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private $product_data = [
        'title' => 'Product Title',
        'summary' => 'Product Summary',
        'description' => 'Product Description',
        'photo' => 'product-photo.jpg',
        'stock' => 10,
        'cat_id' => 1,
        'status' => 'active',
        'condition' => 'default',
        'price' => 19.99,
    ];

    public function create_product()
    {
        $admin_user = [
            'name' => 'Amit Patel',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'status' => 'active',
            'role' => 'admin',
        ];
        $user = User::create(array_merge($admin_user, ['password' => bcrypt($admin_user['password'])]));
        $this->actingAs($user);

        $category = Category::create([
            'title' => 'Category Title',
            'summary' => 'Category Summary',
            'photo' => 'category-photo.jpg',
            'status' => 'active',
        ]);
        $slug = generateUniqueSlug($this->product_data['title'], Product::class);
        $this->product_data['slug'] = $slug;
        $this->product_data['cat_id'] = $category->id;
        $response = $this->post('/admin/product', $this->product_data);
        if ($response->assertSessionHas('success', 'Product Successfully added')) {
            $this->assertTrue(true);
        }
        $response->assertRedirect(route('product.index'));
    }

    public function test_checkout_processes_cart_and_stores_items()
    {
        $user_data = [
            'name' => 'Amit Patel',
            'email' => 'user@example.com',
            'password' => 'password123',
            'status' => 'active',
            'role' => 'user',
        ];
        $this->create_product();
        $product = Product::first();

        $normal_user = User::updateOrCreate(['email' => $user_data['email']], array_merge($user_data, ['password' => bcrypt($user_data['password'])]));
        $this->actingAs($normal_user);
        $this->assertAuthenticatedAs($normal_user);

        // Simulate cart session
        Session::put('cart', [
            [
                'id' => $product->id,
                'quantity' => 2,
                'amount' => 200,
                'price' => 100,
            ],
        ]);

        // Simulate user checkout request
        $response = $this->actingAs($normal_user)->get('/checkout');

        // Assert cart items were saved
        $this->assertDatabaseHas('carts', [
            'user_id' => $normal_user->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'amount' => 200,
            'status' => 'new',
            'price' => 100,
        ]);

        // Assert checkout page is returned
        $response->assertStatus(200);
        $response->assertViewIs('frontend.pages.checkout');
    }

    public function test_checkout_fails_if_cart_is_empty()
    {
        $user_data = [
            'name' => 'Amit Patel',
            'email' => 'user@example.com',
            'password' => 'password123',
            'status' => 'active',
            'role' => 'user',
        ];

        $normal_user = User::updateOrCreate(['email' => $user_data['email']], array_merge($user_data, ['password' => bcrypt($user_data['password'])]));
        $this->actingAs($normal_user);
        $this->assertAuthenticatedAs($normal_user);

        // Ensure cart session is empty
        Session::forget('cart');

        $response = $this->get('/checkout');
        $this->assertDatabaseMissing('carts', ['user_id' => $normal_user->id]);

        $response->assertStatus(200);
        $response->assertViewIs('frontend.pages.checkout');
    }
}
