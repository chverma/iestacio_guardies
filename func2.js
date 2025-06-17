// Función para cargar datos desde SQLite via PHP
async function loadDataFromSQLite(filterDate = null) {
    try {
        const response = await fetch('data.php' + (filterDate ? `?date=${filterDate}` : ''));
        if (!response.ok) {
            throw new Error('Error al cargar datos');
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error:', error);
        return [];
    }
}

// Función para formatear fecha
function formatDate(dateString) {
    if (!dateString) return 'Fecha no disponible';

    try {
        // Convertir de formato dd/mm/yyyy a yyyy-mm-dd para el objeto Date
        if (dateString.includes('/')) {
            const [day, month, year] = dateString.split('/');
            dateString = `${year}-${month}-${day}`;
        }

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

// Función para mostrar los datos
function displayData(data) {
    const container = document.getElementById('cardContainer');

    if (!data || data.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <p>No hay guardias programadas.</p>
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
                    <p><span class="label">Classe:</span> ${item.classe}</p>
                </div>
                <div class="dates-container">
                    <p class="date-item"><span class="label">Dia:</span> ${formatDate(item.dia)}</p>
                    <p class="date-item"><span class="label">Hora:</span> ${item.hora}</p>
                </div>
            </div>
            <p><span class="label">Descripció:</span> ${item.descripcio || 'No hay descripción'}</p>
            <!--div class="contact-info">
                <p><span class="label">Email:</span> ${item.email}</p>
            </div-->
        `;
        container.appendChild(card);
    });
}

// Cargar y mostrar datos al cargar la página
document.addEventListener('DOMContentLoaded', async () => {
    const data = await loadDataFromSQLite();
    displayData(data.data);

    /*
    // Configurar botón de actualización
    document.getElementById('refreshBtn').addEventListener('click', async () => {
        const data = await loadDataFromSQLite();
        displayData(data);
    });

    // Configurar filtros por fecha
    document.getElementById('filterBtn').addEventListener('click', async () => {
        const dateFilter = document.getElementById('dateFilter').value;
        if (dateFilter) {
            const data = await loadDataFromSQLite(dateFilter);
            displayData(data);
        }
    });

    document.getElementById('clearFilter').addEventListener('click', async () => {
        document.getElementById('dateFilter').value = '';
        const data = await loadDataFromSQLite();
        displayData(data);
    });

    */
});
