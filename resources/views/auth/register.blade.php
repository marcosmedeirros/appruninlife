@extends('layouts.app')
@section('content')
<div class="card" style="max-width:400px;margin:auto;">
    <h2>Criar Conta</h2>
    @if($errors->any())
        <div style="color:red;">{{$errors->first()}}</div>
    @endif
    <form method="POST" action="/register">
        @csrf
        <div>
            <label>Nome</label>
            <input type="text" name="name" required style="width:100%;padding:8px;">
        </div>
        <div style="margin-top:10px;">
            <label>Email</label>
            <input type="email" name="email" required style="width:100%;padding:8px;">
        </div>
        <div style="margin-top:10px;">
            <label>Senha</label>
            <input type="password" name="password" required style="width:100%;padding:8px;">
        </div>
        <div style="margin-top:10px;">
            <label>Confirmar Senha</label>
            <input type="password" name="password_confirmation" required style="width:100%;padding:8px;">
        </div>
        <div style="margin-top:20px;">
            <button class="btn" type="submit">Cadastrar</button>
        </div>
        <div style="margin-top:10px;">
            <a href="/login">Já tem conta? Entrar</a>
        </div>
    </form>
</div>
@endsection
