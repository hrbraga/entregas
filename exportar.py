import pandas as pd
from flask_sqlalchemy import SQLAlchemy
from flask import Flask
import os

# Setup a minimal Flask app context to work with SQLAlchemy
app = Flask(__name__)
basedir = os.path.abspath(os.path.dirname(__file__))
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///' + os.path.join(basedir, 'entregas.db')
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
db = SQLAlchemy(app)

# Define as classes do banco de dados para poder fazer a leitura
class ItemEntrega(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    codigo_sap = db.Column(db.String(20), unique=True, nullable=False)
    item = db.Column(db.String(100), nullable=False)
    grupo = db.Column(db.String(50), nullable=False)
    pedido_loja = db.Column(db.Integer, default=0)
    pedido_vd = db.Column(db.Integer, default=0)
    total_caixa = db.Column(db.Integer, default=0)
    a_receber = db.Column(db.Integer, default=0)
    recebido = db.Column(db.Integer, default=0)

class NotaFiscal(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    numero_nota = db.Column(db.String(50), unique=True, nullable=False)
    data_emissao = db.Column(db.String(20), nullable=False)
    data_importacao = db.Column(db.String(20), nullable=False)
    valor_total = db.Column(db.String(20), nullable=False)

# Checa se o arquivo do banco de dados existe antes de tentar ler
if not os.path.exists('entregas.db'):
    print("Erro: O arquivo 'entregas.db' não foi encontrado. Por favor, certifique-se de que o banco de dados existe e contém dados.")
else:
    try:
        # Cria um novo contexto da aplicação
        with app.app_context():
            # Consulta os dados das tabelas
            items_query = db.session.query(ItemEntrega).all()
            notas_query = db.session.query(NotaFiscal).all()

            # Converte para pandas DataFrames
            df_items = pd.DataFrame([item.__dict__ for item in items_query])
            df_notas = pd.DataFrame([nota.__dict__ for nota in notas_query])

            # Limpa os DataFrames removendo a coluna interna do SQLAlchemy
            if '_sa_instance_state' in df_items.columns:
                df_items = df_items.drop(columns=['_sa_instance_state'])
            if '_sa_instance_state' in df_notas.columns:
                df_notas = df_notas.drop(columns=['_sa_instance_state'])
            
            output_file = "relatorio_entregas.xlsx"
            
            # Escreve os DataFrames em um arquivo de Excel com duas abas
            with pd.ExcelWriter(output_file, engine='openpyxl') as writer:
                df_items.to_excel(writer, sheet_name='Itens de Entrega', index=False)
                df_notas.to_excel(writer, sheet_name='Historico de Notas', index=False)
            
            print(f"Sucesso: O arquivo '{output_file}' foi criado com sucesso com os dados do seu projeto.")

    except Exception as e:
        print(f"Ocorreu um erro ao gerar o arquivo Excel: {e}")