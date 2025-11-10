{% extends 'base.html' %}

{% block title %}Login{% endblock %}

{% block head %}
    {{ super() }}
    <link rel="stylesheet" href="{{ url_for('static', filename='css/login.css') }}">
{% endblock %}

{% block content %}
<div class="login-container">
    <form class="login-form" method="POST" action="login_action.php">
        <h2>Login - Controle de Entregas</h2>
        
        {% with messages = get_flashed_messages() %}
        {% if messages %}
            <div class="flash-error">
                {{ messages[0] }}
            </div>
        {% endif %}
        {% endwith %}
        
        <div class="form-group">
            <label for="username">Usuário</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="login-btn">Entrar</button>
        <p class="toggle-link">Não tem uma conta? <a href="{{ url_for('register') }}">Cadastre-se</a></p>
    </form>
</div>
{% endblock %}

{% block scripts %}
{% endblock %}