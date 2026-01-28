@extends('layouts.app')
@section('content')
<div class="card" style="max-width:400px;margin:auto;">
    <h2>Login</h2>
    @if($errors->any())
        <div style="color:red;">{{$errors->first()}}</div>
    @endif
    <form method="POST" action="/login">
        @csrf
        <div>
            <label>Email</label>
            <input type="email" name="email" required autofocus style="width:100%;padding:8px;">
        </div>
        <div style="margin-top:10px;">
            <label>Senha</label>
            <input type="password" name="password" required style="width:100%;padding:8px;">
        </div>
        <div style="margin-top:10px;">
            <input type="checkbox" name="remember"> Lembrar de mim
        </div>
        <div style="margin-top:20px;">
            <button class="btn" type="submit">Entrar</button>
        </div>
        <div style="margin-top:10px;">
            <a href="/forgot-password">Esqueceu a senha?</a>
        </div>
        <div style="margin-top:10px;">
            <a href="/register">Criar conta</a>
        </div>
    </form>
</div>
@endsection
