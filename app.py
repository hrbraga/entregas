from flask import Flask, render_template, request, jsonify, send_from_directory, flash, redirect, url_for
import xml.etree.ElementTree as ET
import os
from flask_sqlalchemy import SQLAlchemy
from datetime import datetime
from sqlalchemy import or_
# CORREÇÃO: Importar 'foreign' não é mais necessário
# from sqlalchemy.orm import foreign 
# NOVAS IMPORTAÇÕES PARA AUTENTICAÇÃO
from flask_login import LoginManager, UserMixin, login_user, logout_user, login_required, current_user
from werkzeug.security import generate_password_hash, check_password_hash
# NOVAS IMPORTAÇÕES PARA IMPORTAR CSV
import pandas as pd
import io

# Define o caminho base do projeto
basedir = os.path.abspath(os.path.dirname(__file__))

# Configuração do Flask
app = Flask(__name__)

# --- CORREÇÃO AQUI ---
# Aponta para as pastas corretas (baseado no seu GitHub)
app.template_folder = os.path.join(basedir, 'templates') 
app.static_folder = os.path.join(basedir, 'static')
# --- FIM DA CORREÇÃO ---

# Configuração do banco de dados (SQLite)
# DB PRINCIPAL: Dados de Entregas
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///' + os.path.join(basedir, 'entregas.db')
# NOVO DB VINCULADO: Dados de Usuários (Logins)
app.config['SQLALCHEMY_BINDS'] = {
    'users': 'sqlite:///' + os.path.join(basedir, 'users.db')
}
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.config['SECRET_KEY'] = 'uma-chave-secreta-muito-dificil-de-adivinhar' 
db = SQLAlchemy(app)

# --- CONFIGURAÇÃO DO FLASK-LOGIN ---
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login' 
login_manager.login_message = "Por favor, faça login para acessar esta página."
login_manager.login_message_category = "flash-error" 

@login_manager.user_loader
def load_user(user_id):
    # Usa db.session.get() que é o método correto e moderno
    return db.session.get(User, int(user_id))

# --- NOVOS MODELOS DE BANCO DE DADOS ---

class User(UserMixin, db.Model):
    __bind_key__ = 'users' # VINCULA ESTE MODELO AO users.db
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    password_hash = db.Column(db.String(256))
    
    # CORREÇÃO: Relações 'items' e 'notas' removidas para
    # evitar o erro 'blank-out primary key' em bancos de dados separados.

    def set_password(self, password):
        self.password_hash = generate_password_hash(password)

    def check_password(self, password):
        # CORREÇÃO DE BUG DE LOGIN: A ordem estava invertida.
        return check_password_hash(self.password_hash, password)

class ItemEntrega(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    codigo_sap = db.Column(db.String(20), nullable=False)
    item = db.Column(db.String(100), nullable=False)
    grupo = db.Column(db.String(50), nullable=False)
    pedido_loja = db.Column(db.Integer, default=0)
    pedido_vd = db.Column(db.Integer, default=0)
    total_caixa = db.Column(db.Integer, default=0)
    a_receber = db.Column(db.Integer, default=0)
    recebido = db.Column(db.Integer, default=0)
    
    # Apenas o ID do usuário. O 'ForeignKey' foi removido para
    # permitir a criação de DBs separados.
    user_id = db.Column(db.Integer, nullable=False, index=True) 
    
    # CORREÇÃO: Relação 'owner' removida.

class NotaFiscal(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    numero_nota = db.Column(db.String(50), nullable=False)
    data_emissao = db.Column(db.String(20), nullable=False)
    data_importacao = db.Column(db.String(20), nullable=False)
    valor_total = db.Column(db.String(20), nullable=False)
    
    # Apenas o ID do usuário.
    user_id = db.Column(db.Integer, nullable=False, index=True)
    
    # CORREÇÃO: Relação 'owner' removida.

class ItemNotaFiscal(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    # Esta FK funciona, pois está no MESMO banco de dados (entregas.db)
    nota_id = db.Column(db.Integer, db.ForeignKey('nota_fiscal.id'), nullable=False)
    codigo_sap = db.Column(db.String(20), nullable=False)
    quantidade = db.Column(db.Float, default=0.0)

# Adiciona relacionamento reverso na NotaFiscal para a exclusão em cascata
NotaFiscal.itens_nota = db.relationship('ItemNotaFiscal', backref='nota', lazy=True, cascade='all, delete-orphan')


# Cria as tabelas do banco de dados se elas não existirem
with app.app_context():
    # 1. Cria as tabelas do bind 'users' (users.db) primeiro.
    db.create_all(bind_key='users')
    # 2. Cria as tabelas do bind padrão (entregas.db).
    db.create_all(bind_key=None) 

# --- ROTAS DE AUTENTICAÇÃO ---

@app.route('/login', methods=['GET', 'POST'])
def login():
    if current_user.is_authenticated:
        return redirect(url_for('recebimentos'))
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        user = User.query.filter_by(username=username).first()
        if user and user.check_password(password):
            login_user(user)
            return redirect(url_for('recebimentos'))
        else:
            flash('Usuário ou senha inválidos.', 'flash-error')
            return redirect(url_for('login'))
    return render_template('login.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if current_user.is_authenticated:
        return redirect(url_for('recebimentos'))
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        if len(username) < 4 or len(password) < 6:
            flash('Usuário ou senha não atendem aos requisitos mínimos.', 'flash-error')
            return redirect(url_for('register'))
        user = User.query.filter_by(username=username).first()
        if user:
            flash('Este nome de usuário já existe.', 'flash-error')
            return redirect(url_for('register'))
        new_user = User(username=username)
        new_user.set_password(password)
        db.session.add(new_user)
        db.session.commit()
        login_user(new_user)
        return redirect(url_for('recebimentos'))
    return render_template('register.html')

@app.route('/logout')
@login_required
def logout():
    logout_user()
    return redirect(url_for('inicio'))


# --- ROTAS PRINCIPAIS (PROTEGIDAS E PÚBLICAS) ---

# Rota principal (/) - PÚBLICA (Custos)
@app.route('/')
def inicio():
    return send_from_directory(os.path.join(app.static_folder, 'custos'), 'inicio.html')

# Rota /entregas - PROTEGIDA (Recebimentos)
@app.route('/entregas')
@login_required 
def recebimentos(): # Antigo 'index'
    return render_template('recebimentos.html') # Renderiza o arquivo renomeado

# Rotas do app de Entregas - PROTEGIDAS
@app.route('/historico')
@login_required
def historico():
    return render_template('historico.html')

@app.route('/dashboard')
@login_required
def dashboard():
    return render_template('dashboard.html')

# Rota para arquivos internos do app Custos - PÚBLICA
@app.route('/custos/<path:filename>')
def custos_static_files(filename):
    return send_from_directory(os.path.join(app.static_folder, 'custos'), filename)

# --- ROTAS DE API (DADOS) - PROTEGIDAS E FILTRADAS ---

@app.route('/import_csv', methods=['POST'])
@login_required
def import_csv():
    if 'file' not in request.files:
        return jsonify({"success": False, "message": "Nenhum arquivo enviado."}), 400
    file = request.files['file']
    if not file.filename.endswith('.csv'):
        return jsonify({"success": False, "message": "Formato de arquivo inválido. Use CSV."}), 400
    try:
        # Limpa APENAS os dados de entrega do usuário atual
        ItemEntrega.query.filter_by(user_id=current_user.id).delete()
        
        data = io.StringIO(file.stream.read().decode("latin-1"))
        df = pd.read_csv(data, delimiter=';', skiprows=6) 
        colunas_csv = df.columns
        df = df.rename(columns={
            colunas_csv[0]: 'codigo_sap',
            colunas_csv[1]: 'item',
            colunas_csv[2]: 'grupo',
            colunas_csv[3]: 'pedido_loja',
            colunas_csv[4]: 'pedido_vd'
        })
        novos_itens = []
        for index, row in df.iterrows():
            if pd.isna(row.get('codigo_sap')) or pd.isna(row.get('item')):
                continue
            pedido_loja = int(row.get('pedido_loja', 0) or 0)
            pedido_vd = int(row.get('pedido_vd', 0) or 0)
            total_caixa = pedido_loja + pedido_vd
            novo_item = ItemEntrega(
                codigo_sap=str(row.get('codigo_sap', '')).lstrip('0'),
                item=row.get('item', ''),
                grupo=row.get('grupo', ''),
                pedido_loja=pedido_loja,
                pedido_vd=pedido_vd,
                total_caixa=total_caixa,
                a_receber=total_caixa,
                recebido=0,
                user_id=current_user.id
            )
            novos_itens.append(novo_item)
        db.session.bulk_save_objects(novos_itens)
        db.session.commit()
        return jsonify({"success": True, "message": "Pedidos importados com sucesso! Os dados anteriores foram substituídos."})
    except Exception as e:
        db.session.rollback()
        print(f"Erro no import_csv: {e}")
        return jsonify({"success": False, "message": "Erro ao processar o arquivo. Verifique o formato e as colunas."}), 500


@app.route('/get_data')
@login_required
def get_data():
    items = ItemEntrega.query.filter_by(user_id=current_user.id).all()
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

@app.route('/search_items')
@login_required
def search_items():
    query = request.args.get('q', '').strip()
    if len(query) < 3:
        return jsonify([])
    codigo_query = query.lstrip('0')
    items = ItemEntrega.query.filter(
        ItemEntrega.user_id == current_user.id,
        or_(
            ItemEntrega.codigo_sap.ilike(f'%{codigo_query}%'),
            ItemEntrega.item.ilike(f'%{query}%')
        )
    ).limit(10).all()
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

@app.route('/get_notas')
@login_required
def get_notas():
    notas = NotaFiscal.query.filter_by(user_id=current_user.id).all()
    notas_list = [
        {
            'numero_nota': nota.numero_nota,
            'valor_total': nota.valor_total,
            'data_emissao': nota.data_emissao,
            'data_importacao': nota.data_importacao
        } for nota in notas
    ]
    return jsonify(notas_list)

@app.route('/get_dashboard_data')
@login_required
def get_dashboard_data():
    items = ItemEntrega.query.filter_by(user_id=current_user.id).all()
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
    
    # CALCULAR POR CAIXAS, NÃO POR SKUS
    grupos = {}
    for item in items:
        if item.grupo not in grupos:
            grupos[item.grupo] = {'nao_entregues': 0, 'parcialmente_entregues': 0, 'totalmente_entregues': 0}
        
        # SOMA A QUANTIDADE DE CAIXAS (total_caixa)
        if item.recebido == 0:
            grupos[item.grupo]['nao_entregues'] += item.total_caixa
        elif item.recebido < item.total_caixa:
            grupos[item.grupo]['parcialmente_entregues'] += item.total_caixa
        elif item.recebido >= item.total_caixa:
            grupos[item.grupo]['totalmente_entregues'] += item.total_caixa
            
    return jsonify({
        "progresso_geral": round(progresso_geral, 2),
        "total_pedido": total_pedido,
        "total_recebido": total_recebido,
        "sku_status": {"nao_entregues": skus_nao_entregues, "parcialmente_entregues": skus_parcialmente_entregues, "totalmente_entregues": skus_totalmente_entregues},
        "grupos": grupos
    })

# ==================================================================
# FUNÇÃO DE UPLOAD DE XML (Salva dados de rastreamento)
# ==================================================================
@app.route('/upload_xml', methods=['POST'])
@login_required
def upload_xml():
    if 'file' not in request.files:
        return jsonify({"success": False, "message": "Nenhum arquivo enviado."}), 400

    files = request.files.getlist('file')
    
    if not files or all(f.filename == '' for f in files):
        return jsonify({"success": False, "message": "Nenhum arquivo selecionado."}), 400

    success_files = []
    failed_files = []

    for file in files:
        if file and file.filename.endswith('.xml'):
            try:
                # É importante "rebobinar" o stream do arquivo antes de processar
                file.stream.seek(0) 
                tree = ET.parse(file)
                root = tree.getroot()
                nfe_tag = root.find('.//{http://www.portalfiscal.inf.br/nfe}NFe')
                inf_nfe_tag = nfe_tag.find('{http://www.portalfiscal.inf.br/nfe}infNFe')
                n_nf = inf_nfe_tag.find('{http://www.portalfiscal.inf.br/nfe}ide/{http://www.portalfiscal.inf.br/nfe}nNF').text
                
                nota_existente = NotaFiscal.query.filter_by(numero_nota=n_nf, user_id=current_user.id).first()
                if nota_existente:
                    failed_files.append(f"{file.filename} (NF {n_nf} já importada)")
                    continue 

                v_nf = inf_nfe_tag.find('{http://www.portalfiscal.inf.br/nfe}total/{http://www.portalfiscal.inf.br/nfe}ICMSTot/{http://www.portalfiscal.inf.br/nfe}vNF').text
                data_emi = inf_nfe_tag.find('{http://www.portalfiscal.inf.br/nfe}ide/{http://www.portalfiscal.inf.br/nfe}dhEmi').text
                data_formatada = data_emi.split('T')[0]
                data_dd_mm_aaaa = '-'.join(reversed(data_formatada.split('-')))
                
                nova_nota = NotaFiscal(
                    numero_nota=n_nf,
                    data_emissao=data_dd_mm_aaaa,
                    data_importacao=datetime.now().strftime('%d-%m-%Y'),
                    valor_total=v_nf,
                    user_id=current_user.id
                )
                db.session.add(nova_nota)
                db.session.flush() # Obtém o ID da nota antes do commit
                
                itens_nota_fiscal = []
                for det_tag in inf_nfe_tag.findall('{http://www.portalfiscal.inf.br/nfe}det'):
                    prod_tag = det_tag.find('{http://www.portalfiscal.inf.br/nfe}prod')
                    c_prod = prod_tag.find('{http://www.portalfiscal.inf.br/nfe}cProd').text
                    q_com = prod_tag.find('{http://www.portalfiscal.inf.br/nfe}qCom').text
                    codigo_sap = c_prod.lstrip('0')
                    quantidade_entregue = float(q_com)
                    
                    item_db = ItemEntrega.query.filter_by(codigo_sap=codigo_sap, user_id=current_user.id).first()
                    if item_db:
                        # 1. ATUALIZA OS DADOS DE ESTOQUE
                        item_db.recebido += quantidade_entregue
                        item_db.a_receber -= quantidade_entregue
                        
                        # 2. RASTREIA O ITEM PARA REVERSÃO FUTURA
                        itens_nota_fiscal.append(ItemNotaFiscal(
                            nota_id=nova_nota.id,
                            codigo_sap=codigo_sap,
                            quantidade=quantidade_entregue
                        ))
                
                db.session.bulk_save_objects(itens_nota_fiscal)
                db.session.commit()
                success_files.append(file.filename)

            except Exception as e:
                db.session.rollback()
                failed_files.append(f"{file.filename} (Erro: {e})")
        
        elif file and not file.filename.endswith('.xml'):
             failed_files.append(f"{file.filename} (Formato inválido)")

    # Cria uma mensagem de resumo
    message = f"Processamento concluído. Sucessos: {len(success_files)}. Falhas: {len(failed_files)}."
    if failed_files:
        message += " Detalhes das falhas: " + "; ".join(failed_files)

    if not success_files and failed_files:
         return jsonify({"success": False, "message": message}), 400
    
    return jsonify({"success": True, "message": message}), 200
# ==================================================================
# FIM DA FUNÇÃO MODIFICADA
# ==================================================================

# ==================================================================
# FUNÇÃO PARA EXCLUIR NOTA FISCAL COM REVERSÃO DE ESTOQUE
# ==================================================================
@app.route('/delete_nota/<string:numero_nota>', methods=['DELETE'])
@login_required
def delete_nota(numero_nota):
    nota = NotaFiscal.query.filter_by(numero_nota=numero_nota, user_id=current_user.id).first()
    if not nota:
        return jsonify({"success": False, "message": "Nota fiscal não encontrada ou não pertence ao seu usuário."}), 404

    try:
        # 1. REVERTE O ESTOQUE DOS ITENS AFETADOS
        for item_nf in ItemNotaFiscal.query.filter_by(nota_id=nota.id).all():
            item_db = ItemEntrega.query.filter_by(codigo_sap=item_nf.codigo_sap, user_id=current_user.id).first()
            if item_db:
                item_db.recebido -= item_nf.quantidade
                item_db.a_receber += item_nf.quantidade
        
        # 2. DELETA OS ITENS VINCULADOS (ItemNotaFiscal)
        # (Isso é tratado pelo 'cascade' no relacionamento, mas é bom garantir)
        ItemNotaFiscal.query.filter_by(nota_id=nota.id).delete()
        
        # 3. DELETA A NOTA FISCAL DO HISTÓRICO
        db.session.delete(nota)
        db.session.commit()
        
        return jsonify({
            "success": True, 
            "message": f"Nota Fiscal {numero_nota} excluída com sucesso! Os itens de entrega foram ajustados."
        })
    except Exception as e:
        db.session.rollback()
        return jsonify({"success": False, "message": f"Ocorreu um erro ao excluir a nota fiscal e reverter estoque: {e}"}), 500


@app.route('/delete_item/<int:item_id>', methods=['DELETE'])
@login_required
def delete_item(item_id):
    try:
        item = ItemEntrega.query.filter_by(id=item_id, user_id=current_user.id).first()
        if item:
            db.session.delete(item)
            db.session.commit()
            return jsonify({"success": True, "message": "Item excluído com sucesso!"})
        else:
            return jsonify({"success": False, "message": "Item não encontrado."}), 404
    except Exception as e:
        db.session.rollback()
        return jsonify({"success": False, "message": f"Ocorreu um erro ao excluir o item: {e}"}), 500

@app.route('/update_item/<int:item_id>', methods=['POST'])
@login_required
def update_item(item_id):
    try:
        item = ItemEntrega.query.filter_by(id=item_id, user_id=current_user.id).first()
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