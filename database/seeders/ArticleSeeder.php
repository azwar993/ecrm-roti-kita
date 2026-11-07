<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = User::query()->where('role', 'admin')->orderBy('id')->value('id');
        $articles = [
            [
                'title' => 'Cara Menyimpan Roti agar Tetap Lembut',
                'slug' => 'cara-menyimpan-roti-agar-tetap-lembut',
                'category' => 'Tips Bakery',
                'seo_title' => 'Cara Menyimpan Roti agar Tetap Lembut | RotiKita Bakery',
                'seo_description' => 'Pelajari cara menyimpan roti agar teksturnya tetap lembut, tidak cepat kering, dan lebih nikmat saat disantap.',
                'excerpt' => 'Roti yang disimpan dengan cara tepat dapat mempertahankan tekstur dan rasanya lebih baik. Kenali cara menyimpan roti sesuai jenis dan bahan isiannya.',
                'body' => <<<'BODY'
Roti merupakan salah satu pilihan makanan yang mudah dinikmati kapan saja. Namun, tekstur roti bisa berubah setelah beberapa waktu. Roti yang awalnya lembut dapat terasa kering, keras, atau kehilangan aroma ketika disimpan dengan cara yang kurang tepat.

Agar roti tetap nikmat, cara penyimpanannya perlu disesuaikan dengan jenis roti, bahan isian, serta kapan roti akan dikonsumsi.

## 1. Tunggu roti tidak panas sebelum disimpan

Jika roti baru selesai dipanggang atau dihangatkan, biarkan uap panasnya berkurang sebelum dimasukkan ke wadah tertutup. Menutup roti ketika masih panas dapat menyebabkan uap air terperangkap dan membuat permukaannya lembap.

Pastikan roti sudah cukup dingin sebelum dikemas.

## 2. Gunakan wadah atau kemasan tertutup

Untuk roti yang tidak langsung dikonsumsi, gunakan kantong makanan atau wadah bersih yang dapat ditutup dengan baik. Cara ini membantu mengurangi paparan udara yang dapat membuat roti lebih cepat kering.

Hindari menyimpan roti bersama makanan yang memiliki aroma sangat kuat karena aroma dapat berpindah.

## 3. Simpan sesuai jenis roti

Roti tawar dan roti dengan isian sederhana dapat disimpan dalam wadah tertutup di tempat yang bersih, kering, dan tidak terkena sinar matahari langsung jika akan segera dikonsumsi.

Roti dengan isian krim, susu, keju, daging, atau bahan yang mudah rusak memerlukan perhatian lebih. Ikuti petunjuk penyimpanan pada kemasan dan simpan dalam lemari pendingin jika memang diperlukan.

## 4. Pertimbangkan freezer untuk penyimpanan lebih lama

Jika roti tidak akan dikonsumsi dalam waktu dekat, freezer dapat menjadi pilihan. Bungkus roti dengan rapat agar tidak mudah terkena udara freezer dan aroma makanan lain.

Ketika ingin menikmatinya, keluarkan secukupnya dan hangatkan kembali sesuai jenis roti.

## 5. Hindari menyimpan roti terlalu lama

Periksa kondisi roti sebelum dikonsumsi. Jika ditemukan jamur atau perubahan yang mencurigakan pada roti, sebaiknya buang seluruh bagian yang terdampak bersama roti tersebut. Jangan hanya menghilangkan bagian yang terlihat berjamur.

## Kesimpulan

Menyimpan roti dengan benar membantu menjaga kualitas tekstur dan rasa. Mulai dari menunggu roti dingin, menggunakan kemasan yang sesuai, sampai memperhatikan bahan isian, setiap langkah dapat membantu roti tetap nikmat ketika akan disantap.
BODY,
            ],
            [
                'title' => 'Mengenal Perbedaan Roti Manis, Roti Tawar, dan Pastry',
                'slug' => 'mengenal-perbedaan-roti-manis-roti-tawar-dan-pastry',
                'category' => 'Edukasi Bakery',
                'seo_title' => 'Perbedaan Roti Manis, Roti Tawar, dan Pastry | RotiKita',
                'seo_description' => 'Kenali karakteristik roti manis, roti tawar, dan pastry dari tekstur, rasa, bahan, serta cara menikmatinya.',
                'excerpt' => 'Sama-sama menjadi pilihan bakery, roti manis, roti tawar, dan pastry memiliki karakteristik yang berbeda. Kenali perbedaannya agar lebih mudah memilih.',
                'body' => <<<'BODY'
Dunia bakery memiliki banyak jenis produk dengan bentuk, rasa, dan tekstur yang beragam. Tiga jenis yang sering ditemui adalah roti manis, roti tawar, dan pastry.

Ketiganya memiliki karakteristik masing-masing. Dengan mengenal perbedaannya, kita bisa memilih produk bakery yang sesuai dengan selera dan kebutuhan.

## 1. Roti manis

Roti manis dikenal dengan cita rasa yang cenderung manis dan tekstur yang umumnya empuk. Jenis roti ini sering dibuat dengan adonan yang menggunakan bahan tambahan seperti gula, lemak, susu, atau telur, tergantung resepnya.

Roti manis dapat hadir dengan berbagai bentuk dan isian. Ada yang disajikan polos, ada pula yang menggunakan isian seperti cokelat, keju, atau bahan lainnya.

Jenis roti ini cocok untuk teman sarapan, camilan sore, atau makanan ringan saat beraktivitas.

## 2. Roti tawar

Roti tawar biasanya memiliki rasa yang lebih netral dibandingkan roti manis. Teksturnya cenderung lembut dengan bagian dalam yang berpori.

Roti tawar mudah dipadukan dengan berbagai bahan tambahan. Kita dapat menikmatinya dengan selai, mentega, telur, keju, atau bahan lain sesuai selera.

Karena rasanya netral, roti tawar juga dapat diolah menjadi berbagai hidangan sederhana.

## 3. Pastry

Pastry merupakan kelompok produk bakery yang memiliki karakteristik tekstur berbeda dari roti pada umumnya. Beberapa jenis pastry dikenal karena lapisan yang renyah dan berlapis.

Pada pastry berlapis, adonan dan lemak disusun melalui proses pelipatan sehingga membentuk lapisan-lapisan ketika dipanggang.

Pastry dapat memiliki rasa manis maupun gurih. Contohnya dapat berupa croissant, danish, puff pastry, serta berbagai variasi lainnya.

Namun, tidak semua pastry mempunyai bentuk atau proses yang sama karena pastry merupakan kelompok produk dengan jenis yang beragam.

## 4. Perbedaan tekstur dan cara menikmatinya

Perbedaan paling mudah dikenali terletak pada tekstur dan karakter rasa.

- Roti manis umumnya empuk dan memiliki rasa manis.
- Roti tawar memiliki rasa yang lebih netral serta mudah dipadukan.
- Pastry tertentu memiliki lapisan yang renyah atau ringan.

Cara menikmatinya juga berbeda. Roti manis dapat disantap sebagai camilan, roti tawar dapat diolah menjadi menu sederhana, sedangkan pastry sering dinikmati sebagai teman minum kopi atau teh.

## Kesimpulan

Setiap produk bakery memiliki keunikan tersendiri. Roti manis, roti tawar, dan pastry bukan hanya berbeda dari bentuknya, tetapi juga karakter rasa, tekstur, dan cara penyajiannya.

Dengan mengenal karakteristik tersebut, kita bisa memilih camilan bakery yang paling sesuai dengan selera.
BODY,
            ],
            [
                'title' => 'Tips Menghangatkan Roti di Rumah',
                'slug' => 'tips-menghangatkan-roti-di-rumah',
                'category' => 'Tips Bakery',
                'seo_title' => 'Tips Menghangatkan Roti di Rumah agar Lebih Nikmat | RotiKita',
                'seo_description' => 'Pelajari beberapa cara menghangatkan roti menggunakan oven, air fryer, teflon, dan microwave dengan hasil yang sesuai jenis roti.',
                'excerpt' => 'Roti yang sudah disimpan dapat terasa lebih nikmat ketika dihangatkan dengan cara yang tepat. Pilih metode sesuai jenis roti dan tekstur yang diinginkan.',
                'body' => <<<'BODY'
Roti sering kali lebih nikmat ketika disajikan dalam kondisi hangat. Aroma yang keluar ketika roti dihangatkan dapat membuat pengalaman menikmati bakery terasa lebih menyenangkan.

Namun, setiap jenis roti membutuhkan cara pemanasan yang berbeda. Metode yang tepat dapat membantu menjaga tekstur agar tidak terlalu kering atau justru menjadi lembek.

## 1. Menggunakan oven

Oven cocok untuk menghangatkan beberapa jenis roti sekaligus. Panaskan oven pada suhu rendah hingga sedang, sekitar 150–160 derajat Celsius.

Masukkan roti selama beberapa menit dan periksa secara berkala. Waktu pemanasan bergantung pada ukuran, ketebalan, dan jenis roti.

Hindari suhu terlalu tinggi karena permukaan roti dapat menjadi terlalu kering atau gosong sebelum bagian dalamnya hangat.

## 2. Menggunakan air fryer

Air fryer dapat digunakan untuk menghangatkan roti dalam jumlah kecil.

Gunakan suhu rendah hingga sedang dan waktu singkat sebagai titik awal. Periksa kondisi roti setiap beberapa menit karena ukuran dan jenis alat dapat memengaruhi hasilnya.

Untuk roti berukuran kecil, proses pemanasan biasanya membutuhkan waktu lebih singkat daripada roti berukuran besar.

## 3. Menggunakan teflon

Teflon dapat menjadi alternatif jika tidak memiliki oven atau air fryer.

Gunakan api kecil. Letakkan roti di atas permukaan teflon dan balik seperlunya agar kedua sisi mendapat panas secara merata.

Cara ini cocok untuk beberapa jenis roti yang ingin dihangatkan secara sederhana.

## 4. Menggunakan microwave

Microwave dapat digunakan ketika ingin menghangatkan roti dengan cepat.

Gunakan waktu singkat terlebih dahulu, misalnya sekitar 10–15 detik, kemudian periksa teksturnya. Jika masih diperlukan, tambahkan waktu sedikit demi sedikit.

Jangan memanaskan terlalu lama karena roti dapat menjadi alot atau keras setelah suhunya turun.

## 5. Perhatikan jenis isian

Roti dengan isian cokelat, keju, krim, atau bahan lainnya bisa memanas dengan cara yang berbeda dari roti polos.

Periksa bagian tengah sebelum dikonsumsi agar tidak terlalu panas. Ikuti petunjuk penyimpanan dan pemanasan apabila tersedia pada kemasan.

Jangan memasukkan kemasan yang tidak dirancang untuk microwave atau oven ke dalam alat pemanas.

## Kesimpulan

Menghangatkan roti tidak harus menggunakan alat yang rumit. Oven, air fryer, teflon, maupun microwave dapat menjadi pilihan sesuai kebutuhan.

Gunakan suhu dan waktu secara bertahap, lalu periksa kondisi roti agar hasilnya tetap nikmat.
BODY,
            ],
            [
                'title' => 'Inspirasi Memilih Camilan Bakery untuk Berbagai Momen',
                'slug' => 'inspirasi-memilih-camilan-bakery-untuk-berbagai-momen',
                'category' => 'Inspirasi',
                'seo_title' => 'Inspirasi Camilan Bakery untuk Berbagai Momen | RotiKita',
                'seo_description' => 'Temukan inspirasi memilih camilan bakery untuk sarapan, teman bekerja, berkumpul bersama keluarga, hingga acara sederhana.',
                'excerpt' => 'Camilan bakery dapat menemani banyak aktivitas. Temukan inspirasi memilih roti dan pastry sesuai suasana, kebutuhan, dan momen kebersamaan.',
                'body' => <<<'BODY'
Camilan tidak selalu harus menjadi bagian dari acara besar. Dalam aktivitas sehari-hari, makanan ringan dapat menjadi teman untuk beristirahat, bekerja, belajar, atau menghabiskan waktu bersama orang terdekat.

Produk bakery memiliki banyak pilihan rasa dan bentuk sehingga mudah disesuaikan dengan berbagai momen.

## 1. Teman memulai pagi

Pagi hari sering menjadi waktu yang sibuk. Pilihan bakery yang praktis dapat menjadi teman sarapan sebelum memulai aktivitas.

Roti dengan rasa yang disukai dapat dipadukan dengan minuman seperti susu, teh, atau kopi. Pilih porsi yang sesuai dengan kebutuhan dan jangan lupa melengkapi sarapan dengan makanan lain jika diperlukan.

## 2. Teman bekerja dan belajar

Saat bekerja atau belajar, camilan dapat menjadi teman ketika mengambil jeda sejenak.

Roti berukuran praktis dapat dipilih karena mudah dinikmati tanpa persiapan yang rumit. Simpan camilan dengan kemasan yang sesuai agar tetap terjaga sampai waktu istirahat.

## 3. Teman menikmati waktu santai

Waktu santai bersama keluarga atau teman dapat terasa lebih menyenangkan dengan sajian bakery.

Kombinasikan beberapa pilihan roti atau pastry sesuai selera orang yang berkumpul. Sediakan juga minuman yang cocok agar suasana terasa lebih hangat.

## 4. Sajian untuk pertemuan kecil

Untuk pertemuan atau diskusi kecil, produk bakery dapat menjadi pilihan sajian yang praktis.

Pilih variasi rasa dan ukuran agar setiap orang memiliki pilihan. Perhatikan jumlah peserta, kebutuhan penyajian, serta cara penyimpanan produk.

## 5. Buah tangan sederhana

Bakery juga dapat menjadi pilihan buah tangan untuk orang terdekat. Perhatikan jenis produk, kemasan, dan ketahanan penyimpanannya.

Untuk produk dengan isian tertentu, pastikan penerima mengetahui petunjuk penyimpanan yang sesuai.

## 6. Sesuaikan pilihan dengan momen

Tidak semua momen memerlukan pilihan yang sama. Untuk sarapan, kita bisa memilih sesuatu yang praktis. Untuk berkumpul, variasi rasa dapat menjadi pertimbangan. Sedangkan untuk buah tangan, kemasan dan petunjuk penyimpanan juga perlu diperhatikan.

## Kesimpulan

Camilan bakery dapat menemani banyak momen sederhana. Dengan memilih jenis produk dan jumlah yang sesuai, kita dapat membuat waktu sarapan, bekerja, bersantai, dan berkumpul terasa lebih menyenangkan.
BODY,
            ],
        ];

        foreach ($articles as $article) {
            Article::query()->firstOrCreate(['slug' => $article['slug']], [
                ...$article,
                'author_id' => $authorId,
                'status' => Article::STATUS_PUBLISHED,
                'published_at' => now()->subDay(),
            ]);
        }
    }
}
