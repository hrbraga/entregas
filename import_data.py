import csv
import os
from app import app, db, ItemEntrega

# Defina o nome do seu arquivo CSV
csv_file_path = os.path.join(os.path.dirname(__file__), 'entregas.csv')

def import_data():
    """Importa os dados do arquivo CSV para a tabela ItemEntrega."""
    print("Iniciando a importação dos dados...")

    try:
        # Abre o arquivo com a codificação correta e o delimitador
        with open(csv_file_path, newline='', encoding='latin-1') as csvfile:
            reader = csv.reader(csvfile, delimiter=';')
            
            # Pula a primeira linha (cabeçalho)
            next(reader)
            
            with app.app_context():
                for row in reader:
                    # Verifica se a linha não está vazia e tem os dados necessários
                    if len(row) > 4 and row[0].strip():
                        try:
                            # Extrai os dados das colunas com os índices corretos
                            codigo_sap = row[0].strip().lstrip('0')
                            item_pascoa = row[1].strip()
                            grupo = row[2].strip()
                            
                            pedido_loja = int(row[3]) if row[3] else 0
                            pedido_vd = int(row[4]) if row[4] else 0
                            
                            total_caixa = pedido_loja + pedido_vd
                            
                            novo_item = ItemEntrega(
                                codigo_sap=codigo_sap,
                                item=item_pascoa,
                                grupo=grupo,
                                pedido_loja=pedido_loja,
                                pedido_vd=pedido_vd,
                                total_caixa=total_caixa,
                                a_receber=total_caixa,
                                recebido=0
                            )
                            
                            db.session.add(novo_item)
                            print(f"Adicionado: {codigo_sap} - {item_pascoa}")
                        
                        except Exception as e:
                            print(f"Erro ao processar a linha: {row}. Erro: {e}")
                            continue

                db.session.commit()
                print("Importação de dados concluída com sucesso!")

    except FileNotFoundError:
        print(f"Erro: O arquivo '{os.path.basename(csv_file_path)}' não foi encontrado. Verifique se o nome do arquivo está correto e se ele está na mesma pasta do script.")
    except Exception as e:
        print(f"Ocorreu um erro inesperado: {e}")

if __name__ == '__main__':
    import_data()