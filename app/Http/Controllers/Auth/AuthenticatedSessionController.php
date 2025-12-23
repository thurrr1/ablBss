<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request. (POST /login)
     *
     * @OA\Post(
     * path="/login",
     * operationId="loginUser",
     * tags={"Authentication"},
     * summary="Masuk (Login) ke Aplikasi Laravel (Web Session)",
     * description="Digunakan untuk mendapatkan sesi otentikasi (cookies) yang diperlukan untuk mengakses endpoint yang dilindungi oleh middleware 'web'.",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"email", "password"},
     * @OA\Property(property="email", type="string", format="email", example="mahasiswa@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="password"),
     * @OA\Property(property="remember", type="boolean", example=true)
     * )
     * ),
     * @OA\Response(
     * response=302,
     * description="Berhasil login, dialihkan ke dashboard.",
     * ),
     * @OA\Response(
     * response=422,
     * description="Kredensial tidak valid.",
     * )
     * )
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}