function csvToJson(csv) {
  const lines = csv.split('\n');
  const result = [];
  const headers = ['nom', 'email', 'titol', 'descripcio', 'data_ini', 'data_fi'];

  for (let i = 0; i < lines.length; i++) {
    if (!lines[i]) continue;
    const obj = {};
    const currentline = lines[i].split(';');

    for (let j = 0; j < headers.length; j++) {
      obj[headers[j].trim()] = currentline[j] ? currentline[j].trim() : '';
    }

    result.push(obj);
  }

  return result;
}


async function loadCSVFromURL(url) {
  const response = await fetch(url);
  const csv = await response.text();
  return csvToJson(csv);
}

// Uso:
loadCSVFromURL('guardies.csv')
  .then(jsonData => {
    console.log(jsonData);
    displayData(jsonData);
  })
  .catch(error => console.error('Error:', error));


function formatDate(dateString) {
    if (!dateString) return 'Fecha no disponible';

    try {
        const date = new Date(dateString);
        return date.toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
}

function displayData(data) {
    const container = document.getElementById('cardContainer');

    if (data.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <p>El archivo no contiene datos válidos.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '';

    data.forEach(item => {
        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `
            <h3>${item.titol}</h3>
            <div class="name-dates-line">
                        <div class="name-container">
                            <p><span class="label">Docent:</span> ${item.nom}</p>
                        </div>
                        <div class="dates-container">
                            <p class="date-item"><span class="label">Inici:</span> ${formatDate(item.data_ini)}</p>
                            <p class="date-item"><span class="label">Fi:</span> ${formatDate(item.data_fi)}</p>
                        </div>
                    </div>
            <p><span class="label">Descripció:</span> ${item.descripcio}</p>

        `;
        container.appendChild(card);
    });
}
