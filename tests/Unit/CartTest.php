<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
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

    /** @test */
    public function add_correct_product_to_cart()
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

        $response = $this->post('/add-to-cart', ['slug' => $product->slug]);
        $response->assertSessionHas('success', 'Product successfully added to cart.');
        $response->assertRedirect(url()->previous());
    }

    /** @test */
    public function add_wrong_product_to_cart()
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

        $response = $this->post('/add-to-cart', ['slug' => 'wrong-slug']);
        if ($response->assertSessionHas('error', 'Invalid Products') || $response->assertSessionHas('error', 'Out of stock, You can add other products.') || $response->assertSessionHas('error', 'Stock not sufficient!.')) {
            $this->assertTrue(true);
        }
        $response->assertRedirect(url()->previous());
    }
}
