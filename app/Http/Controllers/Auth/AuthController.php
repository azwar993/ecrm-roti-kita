<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        // Clicking Login from the public storefront starts a fresh storefront flow.
        // Clear any stale intended URL from an earlier guest session.
        if ($request->query('source') === 'storefront') {
            $request->session()->forget('url.intended');
            $request->session()->forget('rotikita.auth_context');
        }

        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak cocok.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Akun ini sedang dinonaktifkan. Hubungi admin.'])
                ->onlyInput('email');
        }

        // Normal login always returns to the public storefront. Only preserve
        // the intended checkout destination for customers; an old intended URL
        // such as /customer/dashboard must not override the requested home flow.
        if ($user->isAdmin() || $user->isCustomer()) {
            return $this->redirectAfterAuthentication($request, $user)
                ->with('success', 'Berhasil masuk. Selamat datang kembali!');
        }

        return $this->redirectByRole($user);
    }

    public function showRegister(Request $request): View|RedirectResponse
    {
        // Do not let an old protected URL override registration from the storefront.
        if ($request->query('source') === 'storefront') {
            $request->session()->forget(['url.intended', 'rotikita.auth_context']);
        }

        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ], [
            'email.unique' => 'Email tersebut sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi password belum cocok.',
            'terms.accepted' => 'Persetujuan penggunaan data wajib dicentang.',
        ]);

        /**
         * User + customer profile are created in one transaction so we do not
         * leave an account without its CRM profile if the second insert fails.
         * Role is assigned server-side and cannot be chosen on the registration form.
         */
        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                // The User model's "hashed" cast hashes this value automatically.
                'password' => $data['password'],
            ]);

            $user->role = 'customer';
            $user->is_active = true;
            $user->save();

            Customer::create([
                'user_id' => $user->id,
                'customer_code' => 'CUS-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
                'full_name' => $data['name'],
                'contact_email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectAfterAuthentication($request, $user)
            ->with('success', 'Pendaftaran berhasil. Selamat datang di RotiKita!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Kamu berhasil keluar dari akun.');
    }

    /**
     * After login or registration, go to the public home page unless a customer
     * was specifically sent to authentication from checkout. We intentionally
     * ignore other stored intended URLs (for example /customer/dashboard) so a
     * stale session cannot bypass the storefront-first flow.
     */
    private function redirectAfterAuthentication(Request $request, User $user): RedirectResponse
    {
        $intended = $request->session()->pull('url.intended');
        $checkoutContext = $request->session()->pull('rotikita.auth_context') === 'checkout';
        $intendedPath = is_string($intended)
            ? parse_url($intended, PHP_URL_PATH)
            : null;
        $checkoutPath = route('customer.checkout', [], false);

        if ($checkoutContext && $user->isCustomer() && $intendedPath === $checkoutPath) {
            return redirect()->route('customer.checkout');
        }

        return redirect()->route('home');
    }

    private function redirectByRole(?User $user): RedirectResponse
    {
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();
            abort(403, 'Akun ini sedang dinonaktifkan.');
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isCustomer()) {
            return redirect()->route('customer.dashboard');
        }

        Auth::logout();
        abort(403, 'Role akun belum dikonfigurasi.');
    }
}
