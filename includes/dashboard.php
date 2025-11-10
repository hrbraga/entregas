<?php require 'auth_check.php'; ?>

{% extends 'base.html' %}

{% block title %}Dashboard de Entregas{% endblock %}

{% block head %} <link rel="stylesheet" href="{{ url_for('static', filename='css/dashboard.css') }}">
{% endblock %}

{% block content %}
<div class="dashboard-container">
    <div class="card totalizer-card">
        <h2>Totalizador de Entregas</h2>
        
        <div class="totalizer-items-container">
            <div class="totalizer-item">
                <p class="label">TOTAL PEDIDO</p>
                <span class="value" id="total-pedido-val">0</span>
            </div>
            <div class="totalizer-item">
                <p class="label">RECEBIDO</p>
                <span class="value received" id="recebido-val">0</span>
            </div>
            <div class="totalizer-item">
                <p class="label">A RECEBER</p>
                <span class="value to-receive" id="a-receber-val">0</span>
            </div>
        </div>
        
    </div>

    <div class="card">
        <h2>Progresso Geral de Entregas</h2>
        <canvas id="progress-chart"></canvas>
    </div>

    <div class="card">
        <h2>Status de Entrega por SKU</h2>
        <canvas id="sku-status-chart"></canvas>
    </div>

    <div class="card" style="grid-column: 1 / -1;">
        <h2>Status de Entrega por Grupo</h2>
        <canvas id="group-status-chart"></canvas>
    </div>
</div>

{% endblock %}

{% block scripts %}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ url_for('static', filename='js/dashboard.js') }}"></script>
<script src="https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
{% endblock %}