@extends('layouts.app')
@section('content')
<div class="card" style="max-width:400px;margin:auto;">
    <h2>Redefinir Senha</h2>
    @if($errors->any())
        <div style="color:red;">{{$errors->first()}}</div>
    @endif
    <form method="POST" action="/reset-password">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label>Email</label>
            <input type="email" name="email" required style="width:100%;padding:8px;">
        </div>
        <div style="margin-top:10px;">
            <label>Nova Senha</label>
            <input type="password" name="password" required style="width:100%;padding:8px;">
        </div>
        <div style="margin-top:10px;">
            <label>Confirmar Nova Senha</label>
            <input type="password" name="password_confirmation" required style="width:100%;padding:8px;">
        </div>
        <div style="margin-top:20px;">
            <button class="btn" type="submit">Redefinir Senha</button>
        </div>
    </form>
</div>
@endsection
