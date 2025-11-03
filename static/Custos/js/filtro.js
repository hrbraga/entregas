function filterTable() {
    let inputCodigo = document.getElementById("filterCodigo").value.toUpperCase();
    let inputDescricao = document.getElementById("filterDescricao").value.toUpperCase();
    let table = document.querySelector(".tableizer-table tbody");
    let rows = table.getElementsByTagName("tr");

    for (let i = 0; i < rows.length; i++) {
        let codigoCell = rows[i].getElementsByTagName("td")[0];
        let descricaoCell = rows[i].getElementsByTagName("td")[1];

        if (codigoCell && descricaoCell) {
            let codigoText = codigoCell.textContent || codigoCell.innerText;
            let descricaoText = descricaoCell.textContent || descricaoCell.innerText;

            if (codigoText.toUpperCase().includes(inputCodigo) && descricaoText.toUpperCase().includes(inputDescricao)) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
}