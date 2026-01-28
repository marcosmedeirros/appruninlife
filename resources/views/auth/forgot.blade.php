@extends('layouts.app')
@section('content')
<div class="card" style="max-width:400px;margin:auto;">
    <h2>Recuperar Senha</h2>
    @if(session('status'))
        <div style="color:green;">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div style="color:red;">{{$errors->first()}}</div>
    @endif
    <form method="POST" action="/forgot-password">
        @csrf
        <div>
            <label>Email</label>
            <input type="email" name="email" required style="width:100%;padding:8px;">
        </div>
        <div style="margin-top:20px;">
            <button class="btn" type="submit">Enviar link de recuperação</button>
        </div>
        <div style="margin-top:10px;">
            <a href="/login">Voltar ao login</a>
        </div>
    </form>
</div>
@endsection
