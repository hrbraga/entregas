from flask import Flask, render_template, request, jsonify, send_from_directory
import xml.etree.ElementTree as ET
import os
from flask_sqlalchemy import SQLAlchemy
from datetime import datetime
from sqlalchemy import or_

# Define o caminho base do projeto
basedir = os.path.abspath(os.path.dirname(__file__))

# Configuração do Flask
app = Flask(__name__,
            template_folder=os.path.join(basedir, 'templates'), # Aponta para a pasta templates
            static_folder=os.path.join(basedir, 'static'))     # Aponta para a pasta static

# Configuração do banco de dados (SQLite)
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///' + os.path.join(basedir, 'entregas.db')
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
db = SQLAlchemy(app)

# Tabela de Itens de Entrega
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

# Tabela de Notas Fiscais
class NotaFiscal(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    numero_nota = db.Column(db.String(50), unique=True, nullable=False)
    data_emissao = db.Column(db.String(20), nullable=False)
    data_importacao = db.Column(db.String(20), nullable=False)
    valor_total = db.Column(db.String(20), nullable=False)

# Cria as tabelas do banco de dados se elas não existirem
with app.app_context():
    db.create_all()

# --- ROTAS ATUALIZADAS ---

# Rota principal (/) agora serve o projeto de Custos
@app.route('/')
def inicio():
    # Aponta para static/custos/inicio.html
    return send_from_directory(os.path.join(app.static_folder, 'custos'), 'inicio.html')

# Rota /entregas agora serve o app de Controle de Entregas
@app.route('/entregas')
def index():
    return render_template('index.html')

# Rotas do app de Entregas
@app.route('/historico')
def historico():
    return render_template('historico.html')

@app.route('/dashboard')
def dashboard():
    return render_template('dashboard.html')

# --- ROTAS PARA O PROJETO CUSTOS PRODUTOS (Arquivos internos) ---

# Rota "catch-all" para servir os arquivos internos (css, js, html, imagens) do projeto 'Custos'
@app.route('/custos/<path:filename>')
def custos_static_files(filename):
    # Procura o arquivo dentro de static/custos/
    return send_from_directory(os.path.join(app.static_folder, 'custos'), filename)

# --- ROTAS DE API (DADOS) ---

# Rota para obter os dados do banco de dados para a página principal
@app.route('/get_data')
def get_data():
    items = ItemEntrega.query.all()
    
    items_list = [
        {
            'codigo_sap': item.codigo_sap,
            'item': item.item,
            'grupo': item.grupo,
            'pedido_loja': item.pedido_loja,
            'pedido_vd': item.pedido_vd,
            'total_caixa': item.total_caixa,
            'a_receber': item.a_receber,
            'recebido': item.recebido,
            'id': item.id
        } for item in items
    ]
    return jsonify(items_list)

# --- ROTA DE PESQUISA ---
@app.route('/search_items')
def search_items():
    query = request.args.get('q', '').strip()
    
    if len(query) < 3:
        return jsonify([])

    codigo_query = query.lstrip('0')
    
    items = ItemEntrega.query.filter(or_(
        ItemEntrega.codigo_sap.ilike(f'%{codigo_query}%'),
        ItemEntrega.item.ilike(f'%{query}%')
    )).limit(10).all()

    results = [
        {
            'codigo_sap': item.codigo_sap,
            'item': item.item,
            'pedido_total': item.total_caixa,
            'recebido': item.recebido,
            'a_receber': item.a_receber,
            'id': item.id
        } for item in items
    ]
    
    return jsonify(results)

# Rota para obter os dados das notas fiscais para a página de histórico
@app.route('/get_notas')
def get_notas():
    notas = NotaFiscal.query.all()
    notas_list = [
        {
            'numero_nota': nota.numero_nota,
            'valor_total': nota.valor_total,
            'data_emissao': nota.data_emissao,
            'data_importacao': nota.data_importacao
        } for nota in notas
    ]
    return jsonify(notas_list)

# Rota para obter os dados necessários para os gráficos do dashboard
@app.route('/get_dashboard_data')
def get_dashboard_data():
    items = ItemEntrega.query.all()
    
    total_pedido = sum(item.total_caixa for item in items)
    total_recebido = sum(item.recebido for item in items)
    
    progresso_geral = (total_recebido / total_pedido) * 100 if total_pedido > 0 else 0
    
    skus_nao_entregues = 0
    skus_parcialmente_entregues = 0
    skus_totalmente_entregues = 0

    for item in items:
        if item.recebido == 0:
            skus_nao_entregues += 1
        elif item.recebido < item.total_caixa:
            skus_parcialmente_entregues += 1
        elif item.recebido >= item.total_caixa:
            skus_totalmente_entregues += 1
    
    grupos = {}
    for item in items:
        if item.grupo not in grupos:
            grupos[item.grupo] = {
                'nao_entregues': 0,
                'parcialmente_entregues': 0,
                'totalmente_entregues': 0
            }

        if item.recebido == 0:
            grupos[item.grupo]['nao_entregues'] += 1
        elif item.recebido < item.total_caixa:
            grupos[item.grupo]['parcialmente_entregues'] += 1
        elif item.recebido >= item.total_caixa:
            grupos[item.grupo]['totalmente_entregues'] += 1

    return jsonify({
        "progresso_geral": round(progresso_geral, 2),
        "total_pedido": total_pedido,
        "total_recebido": total_recebido,
        "sku_status": {
            "nao_entregues": skus_nao_entregues,
            "parcialmente_entregues": skus_parcialmente_entregues,
            "totalmente_entregues": skus_totalmente_entregues
        },
        "grupos": grupos
    })

# Rota para receber e processar o arquivo XML
@app.route('/upload_xml', methods=['POST'])
def upload_xml():
    if 'file' not in request.files:
        return jsonify({"success": False, "message": "Nenhum arquivo enviado."}), 400

    file = request.files['file']

    if file.filename == '' or not file.filename.endswith('.xml'):
        return jsonify({"success": False, "message": "Arquivo inválido. Por favor, envie um arquivo XML."}), 400

    if file:
        try:
            tree = ET.parse(file)
            root = tree.getroot()

            nfe_tag = root.find('.//{http://www.portalfiscal.inf.br/nfe}NFe')
            inf_nfe_tag = nfe_tag.find('{http://www.portalfiscal.inf.br/nfe}infNFe')
            
            n_nf = inf_nfe_tag.find('{http://www.portalfiscal.inf.br/nfe}ide/{http://www.portalfiscal.inf.br/nfe}nNF').text
            
            nota_existente = NotaFiscal.query.filter_by(numero_nota=n_nf).first()
            if nota_existente:
                return jsonify({"success": False, "message": f"A nota fiscal {n_nf} já foi importada."}), 409

            v_nf = inf_nfe_tag.find('{http://www.portalfiscal.inf.br/nfe}total/{http://www.portalfiscal.inf.br/nfe}ICMSTot/{http://www.portalfiscal.inf.br/nfe}vNF').text
            data_emi = inf_nfe_tag.find('{http://www.portalfiscal.inf.br/nfe}ide/{http://www.portalfiscal.inf.br/nfe}dhEmi').text
            data_formatada = data_emi.split('T')[0]
            data_dd_mm_aaaa = '-'.join(reversed(data_formatada.split('-')))

            nova_nota = NotaFiscal(
                numero_nota=n_nf,
                data_emissao=data_dd_mm_aaaa,
                data_importacao=datetime.now().strftime('%d-%m-%Y'),
                valor_total=v_nf
            )
            db.session.add(nova_nota)

            for det_tag in inf_nfe_tag.findall('{http://www.portalfiscal.inf.br/nfe}det'):
                prod_tag = det_tag.find('{http://www.portalfiscal.inf.br/nfe}prod')
                c_prod = prod_tag.find('{http://www.portalfiscal.inf.br/nfe}cProd').text
                q_com = prod_tag.find('{http://www.portalfiscal.inf.br/nfe}qCom').text

                codigo_sap = c_prod.lstrip('0')
                quantidade_entregue = float(q_com)

                item_db = ItemEntrega.query.filter_by(codigo_sap=codigo_sap).first()
                if item_db:
                    item_db.recebido += quantidade_entregue
                    item_db.a_receber -= quantidade_entregue
            
            db.session.commit()
            return jsonify({"success": True, "message": "Arquivo processado e dados atualizados com sucesso!"}), 200

        except ET.ParseError as e:
            return jsonify({"success": False, "message": f"Erro ao analisar o XML: {e}"}), 400
        except Exception as e:
            return jsonify({"success": False, "message": f"Ocorreu um erro no servidor: {e}"}), 500

# Rota para excluir um item
@app.route('/delete_item/<int:item_id>', methods=['DELETE'])
def delete_item(item_id):
    try:
        item = ItemEntrega.query.get(item_id)
        if item:
            db.session.delete(item)
            db.session.commit()
            return jsonify({"success": True, "message": "Item excluído com sucesso!"})
        else:
            return jsonify({"success": False, "message": "Item não encontrado."}), 404
    except Exception as e:
        db.session.rollback()
        return jsonify({"success": False, "message": f"Ocorreu um erro ao excluir o item: {e}"}), 500

# Rota para editar um item
@app.route('/update_item/<int:item_id>', methods=['POST'])
def update_item(item_id):
    try:
        item = ItemEntrega.query.get(item_id)
        if not item:
            return jsonify({"success": False, "message": "Item não encontrado."}), 404

        data = request.get_json()
        
        for field, value in data.items():
            if field in ['pedido_loja', 'pedido_vd']:
                setattr(item, field, value)
            
        item.total_caixa = item.pedido_loja + item.pedido_vd
        item.a_receber = item.total_caixa - item.recebido

        db.session.commit()
        return jsonify({"success": True, "message": "Item atualizado com sucesso!"})

    except Exception as e:
        db.session.rollback()
        return jsonify({"success": False, "message": f"Ocorreu um erro ao atualizar o item: {e}"}), 500

if __name__ == '__main__':
    app.run(debug=True)