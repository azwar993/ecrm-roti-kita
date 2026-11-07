<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticlePublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_metadata_migration_adds_and_reverses_only_its_columns(): void
    {
        $migration = require database_path('migrations/2026_11_03_000001_add_metadata_to_articles_table.php');

        $this->assertTrue(Schema::hasColumn('articles', 'category'));
        $migration->down();
        $this->assertFalse(Schema::hasColumn('articles', 'category'));
        $this->assertTrue(Schema::hasColumn('articles', 'title'));
        $migration->up();
        $this->assertTrue(Schema::hasColumn('articles', 'seo_description'));
    }

    public function test_article_seeder_is_idempotent_and_does_not_overwrite_admin_edits(): void
    {
        $this->seed(ArticleSeeder::class);
        $expectedArticles = [
            'cara-menyimpan-roti-agar-tetap-lembut' => ['Cara Menyimpan Roti agar Tetap Lembut', 'Tips Bakery'],
            'mengenal-perbedaan-roti-manis-roti-tawar-dan-pastry' => ['Mengenal Perbedaan Roti Manis, Roti Tawar, dan Pastry', 'Edukasi Bakery'],
            'tips-menghangatkan-roti-di-rumah' => ['Tips Menghangatkan Roti di Rumah', 'Tips Bakery'],
            'inspirasi-memilih-camilan-bakery-untuk-berbagai-momen' => ['Inspirasi Memilih Camilan Bakery untuk Berbagai Momen', 'Inspirasi'],
        ];
        $this->assertSame(4, Article::query()->count());
        foreach ($expectedArticles as $slug => [$title, $category]) {
            $seededArticle = Article::query()->where('slug', $slug)->firstOrFail();
            $this->assertSame($title, $seededArticle->title);
            $this->assertSame($slug, $seededArticle->slug);
            $this->assertSame($category, $seededArticle->category);
            $this->assertNotEmpty($seededArticle->excerpt);
            $this->assertNotEmpty($seededArticle->seo_title);
            $this->assertNotEmpty($seededArticle->seo_description);
            $this->assertGreaterThan(500, strlen($seededArticle->body));
            $this->assertDoesNotMatchRegularExpression('/lorem ipsum|isi artikel|coming soon/i', $seededArticle->body);
            $this->assertSame(Article::STATUS_PUBLISHED, $seededArticle->status);
            $this->assertNotNull($seededArticle->published_at);
            $this->assertTrue($seededArticle->published_at->lessThanOrEqualTo(now()));
            $this->get(route('articles.show', $seededArticle))->assertOk()
                ->assertSee($title)
                ->assertSee($category)
                ->assertSee($seededArticle->seo_description, false);
        }

        $articleIndex = $this->get(route('articles.index'))->assertOk();
        foreach ($expectedArticles as [$title]) {
            $articleIndex->assertSee($title);
        }
        $homepage = $this->get(route('home'))->assertOk();
        $this->assertSame(3, substr_count($homepage->getContent(), 'Baca selengkapnya'));

        $article = Article::query()->where('slug', 'cara-menyimpan-roti-agar-tetap-lembut')->firstOrFail();
        $article->update(['title' => 'Judul hasil edit admin', 'status' => Article::STATUS_DRAFT]);

        $this->seed(ArticleSeeder::class);

        $this->assertSame(4, Article::query()->count());
        $this->assertDatabaseHas('articles', [
            'slug' => 'cara-menyimpan-roti-agar-tetap-lembut',
            'title' => 'Judul hasil edit admin',
            'status' => Article::STATUS_DRAFT,
        ]);
    }

    public function test_admin_can_publish_an_article_and_public_pages_only_show_published_content(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.articles.store'), [
            'title' => 'Tips Menyimpan Roti',
            'excerpt' => 'Cara menjaga roti tetap nikmat.',
            'body' => '<script>alert(1)</script>'."\nSimpan roti dalam wadah tertutup.",
            'category' => 'Tips Roti',
            'seo_title' => 'Tips menyimpan roti',
            'seo_description' => 'Panduan penyimpanan roti di rumah.',
            'status' => Article::STATUS_DRAFT,
            'cover' => UploadedFile::fake()->image('roti.jpg'),
        ])->assertRedirect();

        $article = Article::query()->firstOrFail();
        $this->assertSame('Tips Roti', $article->category);
        $this->assertSame('Tips menyimpan roti', $article->seo_title);
        $this->assertSame('Panduan penyimpanan roti di rumah.', $article->seo_description);
        Storage::disk('public')->assertExists($article->cover_path);
        $this->get(route('home'))->assertOk()->assertDontSee($article->title);
        $this->get(route('articles.index'))->assertOk()->assertDontSee($article->title);
        $this->get(route('articles.show', $article))->assertNotFound();

        $this->actingAs($admin)->put(route('admin.articles.update', $article), [
            'title' => $article->title,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'status' => Article::STATUS_PUBLISHED,
        ])->assertRedirect();

        $article->refresh();
        $this->assertNotNull($article->published_at);
        $this->get(route('home'))->assertOk()->assertSee($article->title);
        $this->get(route('articles.index'))->assertOk()->assertSee($article->title);
        $this->get(route('articles.show', $article))->assertOk()
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_future_published_articles_are_not_visible_and_missing_metadata_is_preserved_on_edit(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $article = Article::query()->create([
            'title' => 'Roti terjadwal',
            'slug' => 'roti-terjadwal',
            'body' => 'Konten edukatif.',
            'category' => 'Edukasi Bakery',
            'seo_title' => 'Judul SEO tersimpan',
            'seo_description' => 'Deskripsi SEO tersimpan.',
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('articles.index'))->assertOk()->assertDontSee($article->title);
        $this->get(route('articles.show', $article))->assertNotFound();

        $this->actingAs($admin)->put(route('admin.articles.update', $article), [
            'title' => 'Roti terjadwal diperbarui',
            'body' => 'Konten diperbarui.',
            'status' => Article::STATUS_DRAFT,
        ])->assertRedirect();

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'category' => 'Edukasi Bakery',
            'seo_title' => 'Judul SEO tersimpan',
            'seo_description' => 'Deskripsi SEO tersimpan.',
        ]);
    }

    public function test_storefront_contact_links_use_only_configured_merchant_values(): void
    {
        config()->set('rotikita-contact.email', 'hello@rotikita.example');
        config()->set('rotikita-contact.whatsapp', '+62 812-3456-7890');
        config()->set('rotikita-contact.instagram', 'https://instagram.com/rotikita.example');
        config()->set('rotikita-contact.address', 'Jalan Roti 1, Jakarta');
        config()->set('rotikita-contact.opening_hours', 'Setiap hari 08.00-20.00');

        $this->get(route('home'))->assertOk()
            ->assertSee('mailto:hello@rotikita.example', false)
            ->assertSee('https://wa.me/6281234567890', false)
            ->assertSee('https://instagram.com/rotikita.example', false)
            ->assertSee('google.com/maps/search/', false)
            ->assertSee('Ikuti Keseruan');

        config()->set('rotikita-contact.email', null);
        config()->set('rotikita-contact.whatsapp', null);
        config()->set('rotikita-contact.instagram', null);
        config()->set('rotikita-contact.address', null);
        config()->set('rotikita-contact.opening_hours', null);

        $this->get(route('home'))->assertOk()
            ->assertDontSee('mailto:', false)
            ->assertDontSee('wa.me/', false)
            ->assertDontSee('instagram.com/', false)
            ->assertSee('Informasi kontak toko akan ditampilkan');
    }
}
