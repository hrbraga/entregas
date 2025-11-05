# 1. Usar uma imagem base oficial do Python (use a versão mais próxima da sua)
FROM python:3.11-slim

# 2. Definir o diretório de trabalho dentro do container
WORKDIR /app

# 3. Definir variáveis de ambiente para o Python
ENV PYTHONDONTWRITEBYTECODE 1
ENV PYTHONUNBUFFERED 1

# 4. Instalar as bibliotecas
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# 5. Copiar todo o seu projeto para dentro do container
COPY . .

# 6. Comando para iniciar o servidor de produção (Gunicorn)
# Ele irá rodar o objeto 'app' dentro do seu arquivo 'app.py'
# O 'db.create_all()' será executado automaticamente pelo 'app.py' ao iniciar
# Expõe a porta 8080, que é o padrão do Cloud Run
CMD ["gunicorn", "--bind", "0.0.0.0:8080", "app:app"]