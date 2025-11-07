#!/bin/bash
cd /home/hrbraga87/entregas
source venv/bin/activate
git pull
pip install -r requirements.txt
touch /var/www/hrbraga87_pythonanywhere_com_wsgi.py
echo "✅ Projeto atualizado com sucesso!"
