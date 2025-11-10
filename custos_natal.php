<!DOCTYPE html>
<html lang="pr-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="../src/img/natal.ico" type="image/x-icon">
  <link rel="stylesheet" href="../css/planilhas.css">
  <link rel="stylesheet" href="../css/global.css">
  <title>Custos Natal</title>
</head>

<body>
  <header>
    <h1>Natal 2024</h1>
      <div class="botoes">
            <div class="btn-importar botoesImportacaoEExportacao">
                <p>IMPORTAR ARQUIVO</p>
                <button id="btn-importar">IMPORTAR</button>
                <input type="file" id="importXLS" accept=".xlsx, .xls" style="display: none;">
            </div>
          
            <div class="btn-exportar botoesImportacaoEExportacao">
                <p>EXPORTAR</p>
                <button onclick="exportToPDF()">PDF</button>
                <button onclick="exportToXLS()">XLS</button>
            </div>
            
            <div class="btn-limpar botoesImportacaoEExportacao">
                <p>LIMPAR TUDO</p>
                <button id="btn-limpar">LIMPAR</button>
            </div>
        </div>
  </header>

  <main>
    <div class="transferencia">
      <div class="lojas">
        <label class="remetente" for="remetente">Loja remetente:<input type="text" name="remetente"
            id="remetente"></label>
        <label class="destino" for="destino">Loja destino:<input type="text" name="destino" id="destino"></label>
        <label class="data" for="data">Data da Transferência:<input type="date" name="date" id="date"></label>
      </div>
      <div class="valores-transferencia">
        <p class="total-transferencia">Total Transferência:</p>
        <span class="vlr-transferencia" id="vlr-transferencia">0,00</span>
      </div>
    </div>

    <table class="tableizer-table">
      <tr class="tableizer-firstrow">
        <th>
            Código<br>
            <input type="text" id="filterCodigo" onkeyup="filterTable()" placeholder="Filtrar...">
        </th>
        <th>
            Descrição do Material<br>
            <input type="text" id="filterDescricao" onkeyup="filterTable()" placeholder="Filtrar...">
        </th>
        <th>QT caixa</th>
        <th>caixas</th>
        <th>unidades</th>
        <th>Valor Un</th>
        <th>Royalties</th>
        <th>ST</th>
        <th>IPI</th>
        <th>Txs Adicionais</th>
        <th>Tx Mídia</th>
        <th>Custo Caixa</th>
        <th>Custo UN</th>
        <th>Total</th>
    </tr>
      </thead>
      <tbody class="tableizer-linhas">
        <tr>
          <td>1002209</td>
          <td>ARVORE DE NATAL 70GX24UN</td>
          <td>24</td>
          <td><input type="number" class="caixas" min="0"></td>
          <td><input type="number" class="unidades" min="0"></td>
          <td>158,41</td>
          <td>79,21</td>
          <td>15,20</td>
          <td>5,15</td>
          <td>2,16</td>
          <td>12,00</td>
          <td>272,13</td>
          <td>11,34</td>
          <td><span class="total-item" id="total-valor">0.00</span></td>
        </tr>
        <tr>
          <td>1003449</td>
          <td>BB DEGUSTACAO MF LIMAO 1,5KGX1UN</td>
          <td>1</td>
          <td><input type="number" class="caixas" min="0"></td>
          <td><input type="number" class="unidades" min="0"></td>
          <td>36,60</td>
          <td>18,30</td>
          <td>0,00</td>
          <td>1,19</td>
          <td>0,00</td>
          <td>0,00</td>
          <td>56,09</td>
          <td>56,09</td>
          <td><span class="total-item" id="total-valor">0.00</span></td>
        </tr>
        <tr>
          <td>1002837</td>
          <td>BB FLOWPACK ARVORE MIL FOLHAS 78GX12UN</td>
          <td>12</td>
          <td><input type="number" class="caixas" min="0"></td>
          <td><input type="number" class="unidades" min="0"></td>
          <td>69,89</td>
          <td>34,95</td>
          <td>6,71</td>
          <td>2,27</td>
          <td>1,08</td>
          <td>6,00</td>
          <td>120,90</td>
          <td>10,07</td>
          <td><span class="total-item" id="total-valor">0.00</span></td>
        </tr>
        <tr>
          <td>1003194</td>
          <td>BOMBONS SORTIDOS DE NATAL 40GX36UN</td>
          <td>36</td>
          <td><input type="number" class="caixas" min="0"></td>
          <td><input type="number" class="unidades" min="0"></td>
          <td>115,73</td>
          <td>57,87</td>
          <td>11,11</td>
          <td>3,76</td>
          <td>3,24</td>
          <td>18,00</td>
          <td>209,71</td>
          <td>5,83</td>
          <td><span class="total-item" id="total-valor">0.00</span></td>
        </tr>
    <tr>
        <td>1001963</td>
        <td>CAIXA FELIZ NATAL 95GX14UN</td>
        <td>14</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>151,94</td>
        <td>75,97</td>
        <td>14,58</td>
        <td>4,94</td>
        <td>1,26</td>
        <td>21,00</td>
        <td>269,69</td>
        <td>19,26</td>
        <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1001964</td>
        <td>CAIXA MAGIA DO NATAL 196Gx12UN</td>
        <td>12</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>279,56</td>
        <td>139,78</td>
        <td>26,83</td>
        <td>9,09</td>
        <td>1,08</td>
        <td>6,00</td>
        <td>462,34</td>
        <td>38,53</td>
        <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>2004784</td>
        <td>CESTA PRESENTE NATAL M 12UN CH</td>
        <td>12</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>183,61</td>
        <td>91,81</td>
        <td>0,00</td>
        <td>17,90</td>
        <td>1,08</td>
        <td>0,00</td>
        <td>294,40</td>
        <td>24,53</td>
        <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>2004782</td>
        <td>CESTA PRESENTE NATAL P 12UN CH</td>
        <td>12</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>143,60</td>
        <td>71,80</td>
        <td>0,00</td>
        <td>14,00</td>
        <td>1,08</td>
        <td>0,00</td>
        <td>230,48</td>
        <td>19,21</td>
        <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1000339</td>
        <td>CHOCOARTE BOAS FESTAS LEITE 40GX24UN</td>
        <td>24</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>121,88</td>
        <td>60,94</td>
        <td>13,45</td>
        <td>3,96</td>
        <td>2,16</td>
        <td>0,00</td>
        <td>202,39</td>
        <td>8,43</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1000650</td>
        <td>CUBO BOMBONS CLASSICOS 145GX24UN</td>
        <td>24</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>278,32</td>
        <td>139,16</td>
        <td>26,71</td>
        <td>9,05</td>
        <td>2,16</td>
        <td>24,00</td>
        <td>479,40</td>
        <td>19,98</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003072</td>
        <td>CX CS BOMBONS SORTIDOS M 160GX4UN</td>
        <td>4</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>106,38</td>
        <td>53,19</td>
        <td>10,21</td>
        <td>3,46</td>
        <td>0,36</td>
        <td>0,00</td>
        <td>173,60</td>
        <td>43,40</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003200</td>
        <td>CX DRAGEADO CEREJA 200GX9UN</td>
        <td>9</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>103,43</td>
        <td>51,72</td>
        <td>22,71</td>
        <td>3,36</td>
        <td>0,81</td>
        <td>4,50</td>
        <td>186,53</td>
        <td>20,73</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1002208</td>
        <td>CX GOURMET 255GX12UN</td>
        <td>12</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>354,86</td>
        <td>177,43</td>
        <td>34,06</td>
        <td>11,53</td>
        <td>1,08</td>
        <td>12,00</td>
        <td>590,96</td>
        <td>49,25</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003480</td>
        <td>CX GOURMET 465GX10UN</td>
        <td>10</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>436,68</td>
        <td>218,34</td>
        <td>41,91</td>
        <td>14,19</td>
        <td>0,90</td>
        <td>10,00</td>
        <td>722,02</td>
        <td>72,20</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1000690</td>
        <td>CX LACREME TRUFAS 225GX12UN</td>
        <td>12</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>313,19</td>
        <td>156,60</td>
        <td>30,06</td>
        <td>10,18</td>
        <td>1,08</td>
        <td>12,00</td>
        <td>523,11</td>
        <td>43,59</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003180</td>
        <td>CX MINISHOW LACREME SORT NATAL 108GX16UN</td>
        <td>16</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>161,77</td>
        <td>80,89</td>
        <td>15,52</td>
        <td>5,26</td>
        <td>0,72</td>
        <td>16,00</td>
        <td>280,16</td>
        <td>17,51</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003227</td>
        <td>DISPLAY BISCOITOS DE NATAL SORT 300GX5UN</td>
        <td>5</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>100,29</td>
        <td>50,15</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>0,45</td>
        <td>5,00</td>
        <td>155,89</td>
        <td>31,18</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1002488</td>
        <td>DISPLAY ESTRELA NATAL 120GX24UN</td>
        <td>24</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>219,96</td>
        <td>109,98</td>
        <td>21,11</td>
        <td>7,15</td>
        <td>2,16</td>
        <td>0,00</td>
        <td>360,36</td>
        <td>15,02</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003170</td>
        <td>DISPLAY ESTRELA ZA AO LEITE 120GX24UN</td>
        <td>24</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>261,02</td>
        <td>130,51</td>
        <td>25,05</td>
        <td>8,49</td>
        <td>2,16</td>
        <td>0,00</td>
        <td>427,23</td>
        <td>17,80</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003141</td>
        <td>DISPLAY ESTRELA ZL AO LEITE 120GX24UN</td>
        <td>24</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>261,02</td>
        <td>130,51</td>
        <td>25,05</td>
        <td>8,49</td>
        <td>2,16</td>
        <td>0,00</td>
        <td>427,23</td>
        <td>17,80</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003444</td>
        <td>MINICUBO SNOOPY COLECIONAVEL 47GX24UN</td>
        <td>24</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>176,01</td>
        <td>88,01</td>
        <td>38,65</td>
        <td>5,72</td>
        <td>2,16</td>
        <td>0,00</td>
        <td>310,55</td>
        <td>12,94</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1002204</td>
        <td>MONTEBELLO TRADICIONAL NATAL 90GX12UN</td>
        <td>12</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>70,61</td>
        <td>35,31</td>
        <td>8,99</td>
        <td>2,29</td>
        <td>1,08</td>
        <td>6,00</td>
        <td>124,28</td>
        <td>10,36</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003222</td>
        <td>PANET FONDUE MIL FOLHAS AVELA 800GX8UN</td>
        <td>8</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>377,35</td>
        <td>188,68</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>0,72</td>
        <td>0,00</td>
        <td>566,75</td>
        <td>70,84</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003197</td>
        <td>PANET PREMIUM FONDUE PISTACHE 800GX8UN</td>
        <td>8</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>465,77</td>
        <td>232,89</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>0,72</td>
        <td>0,00</td>
        <td>699,38</td>
        <td>87,42</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1002205</td>
        <td>PANETTONE BRIGADEIRO 580GX12UN</td>
        <td>12</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>313,91</td>
        <td>156,96</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>1,08</td>
        <td>12,00</td>
        <td>483,95</td>
        <td>40,33</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003189</td>
        <td>PANETTONE CHOCOLATE AO LEITE 420GX18UN</td>
        <td>18</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>435,88</td>
        <td>217,94</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>1,62</td>
        <td>0,00</td>
        <td>655,44</td>
        <td>36,41</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1002214</td>
        <td>PANETTONE CLASSICO MIAU 650GX8UN</td>
        <td>8</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>270,36</td>
        <td>135,18</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>0,72</td>
        <td>16,00</td>
        <td>422,26</td>
        <td>52,78</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1001256</td>
        <td>PANETTONE DEGUST TIRAMISSU 750GX8UN</td>
        <td>8</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>266,13</td>
        <td>133,07</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>399,20</td>
        <td>49,90</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003486</td>
        <td>PANETTONE DREAMS TIRAMISU 750GX8UN</td>
        <td>8</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>362,52</td>
        <td>181,26</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>0,72</td>
        <td>0,00</td>
        <td>544,50</td>
        <td>68,06</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1000326</td>
        <td>PANETTONE DREAMS TRUFADO GOTAS 750GX8UN</td>
        <td>8</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>357,86</td>
        <td>178,93</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>0,72</td>
        <td>12,00</td>
        <td>549,51</td>
        <td>68,69</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003470</td>
        <td>PANETTONE FRUTAS 450GX18UN</td>
        <td>18</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>251,41</td>
        <td>125,71</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>1,62</td>
        <td>0,00</td>
        <td>378,74</td>
        <td>21,04</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003469</td>
        <td>PANETTONE GOTAS 450GX18UN</td>
        <td>18</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>268,37</td>
        <td>134,19</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>1,62</td>
        <td>0,00</td>
        <td>404,18</td>
        <td>22,45</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003464</td>
        <td>PANETTONE HARRY POTTER 500GX8UN</td>
        <td>8</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>262,22</td>
        <td>131,11</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>0,72</td>
        <td>0,00</td>
        <td>394,05</td>
        <td>49,26</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003489</td>
        <td>PANETTONE LACREME 650GX12UN</td>
        <td>12</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>406,13</td>
        <td>203,07</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>1,08</td>
        <td>12,00</td>
        <td>622,28</td>
        <td>51,86</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003461</td>
        <td>PANETTONE LACREME SUPREME 1KGX8UN</td>
        <td>8</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>452,26</td>
        <td>226,13</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>0,72</td>
        <td>0,00</td>
        <td>679,11</td>
        <td>84,89</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1002476</td>
        <td>PANETTONE LACREME ZERO ACUCAR 650GX12UN</td>
        <td>12</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>486,29</td>
        <td>243,15</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>1,08</td>
        <td>12,00</td>
        <td>742,52</td>
        <td>61,88</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003490</td>
        <td>PANETTONE LIMAO SICILIANO 580GX12UN</td>
        <td>12</td>        
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>313,91</td>
        <td>156,96</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>1,08</td>
        <td>0,00</td>
        <td>471,95</td>
        <td>39,33</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003186</td>
        <td>PANETTONE MINI BRIGADEIRO 130GX18UN</td>
        <td>18</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>        
        <td>178,03</td>
        <td>89,02</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>1,62</td>
        <td>0,00</td>
        <td>268,67</td>
        <td>14,93</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1000668</td>
        <td>PANETTONE PREMIUM LACREME 750GX8UN</td>
        <td>8</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>389,68</td>
        <td>194,84</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>0,72</td>
        <td>12,00</td>
        <td>597,24</td>
        <td>74,66</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003185</td>
        <td>PANETTONE TRUFADO MEZZO 580GX12UN</td>
        <td>12</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>313,91</td>
        <td>156,96</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>1,08</td>
        <td>24,00</td>
        <td>495,95</td>
        <td>41,33</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1002482</td>
        <td>PAPAI NOEL ART CACAU MAGIA 70GX24UN</td>
        <td>24</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>201,23</td>
        <td>100,62</td>
        <td>25,62</td>
        <td>6,54</td>
        <td>2,16</td>
        <td>0,00</td>
        <td>336,17</td>
        <td>14,01</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1001957</td>
        <td>PEDACOS PANETTONES COBERTOS 200GX6UN</td>
        <td>6</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>122,25</td>
        <td>61,13</td>
        <td>0,00</td>
        <td>0,00</td>
        <td>0,54</td>
        <td>6,00</td>
        <td>189,92</td>
        <td>31,65</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>2004802</td>
        <td>PELUCIA SNOOPY NATAL 15UN CH</td>
        <td>15</td>    
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>240,08</td>
        <td>120,04</td>
        <td>0,00</td>
        <td>15,61</td>
        <td>1,35</td>
        <td>0,00</td>
        <td>377,08</td>
        <td>25,14</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003201</td>
        <td>TAB DISPLAY NATALINOS AO LEI 120GX12UN</td>
        <td>12</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>139,40</td>
        <td>69,70</td>
        <td>15,38</td>
        <td>4,53</td>
        <td>1,08</td>
        <td>12,00</td>
        <td>242,09</td>
        <td>20,17</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1001216</td>
        <td>TABLETE DE NATAL PAPAI NOEL 54GX36UN</td>
        <td>36</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>174,07</td>
        <td>87,04</td>
        <td>19,20</td>
        <td>5,66</td>
        <td>3,24</td>
        <td>36,00</td>
        <td>325,21</td>
        <td>9,03</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003439</td>
        <td>TABLETE SNOOPY AO LEITE 100GX38UN</td>
        <td>38</td>  
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>296,21</td>
        <td>148,11</td>
        <td>32,68</td>
        <td>9,63</td>
        <td>3,42</td>
        <td>19,00</td>
        <td>509,05</td>
        <td>13,40</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003457</td>
        <td>TABLETE SNOOPY VERDE AO LEITE 100GX38UN</td>
        <td>38</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>296,21</td>
        <td>148,11</td>
        <td>32,68</td>
        <td>9,63</td>
        <td>3,42</td>
        <td>19,00</td>
        <td>509,05</td>
        <td>13,40</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003456</td>
        <td>TABLETE SNOOPY VERM AO LEITE 100GX38UN</td>
        <td>38</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>296,21</td>
        <td>148,11</td>
        <td>32,68</td>
        <td>9,63</td>
        <td>3,42</td>
        <td>19,00</td>
        <td>509,05</td>
        <td>13,40</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1003142</td>
        <td>TRUFA ART PANETTONE 30GX90UN</td>
        <td>90</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>184,38</td>
        <td>92,19</td>
        <td>23,47</td>
        <td>5,99</td>
        <td>8,10</td>
        <td>9,00</td>
        <td>323,13</td>
        <td>3,59</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
      <tr>
        <td>1002842</td>
        <td>TRUFA REC BELGA 30GX90UN</td>
        <td>90</td>
        <td><input type="number" class="caixas" min="0"></td>
        <td><input type="number" class="unidades" min="0"></td>
        <td>162,05</td>
        <td>81,03</td>
        <td>20,63</td>
        <td>5,27</td>
        <td>8,10</td>
        <td>9,00</td>
        <td>286,08</td>
        <td>3,18</td>
       <td><span class="total-item" id="total-valor">0.00</span></td>
      </tr>
    </tbody>
  </table>
</main>

  <footer>
    <a href="..\html\selecao.html">
      <p>Voltar ao Início</p>
    </a>
  </footer>

  <script src="../js/dados.js"></script>
    <script src="../js/gerador.js"></script>
    <script src="../js/btn-export.js"></script>
    <script src="../js/filtro.js"></script>
    <script src="../js/import-xls.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/shim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

</body>

</html>