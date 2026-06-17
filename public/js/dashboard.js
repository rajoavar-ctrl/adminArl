window.cargarDashboard = async function() {

    try {

        const res =
            await fetch('/orange-proyect/public/dashboard');

        const data =
            await res.json();

        document.getElementById('totalEmpresas').innerText =
            data.empresas || 0;

        document.getElementById('totalConductores').innerText =
            data.conductores || 0;

        document.getElementById('totalVehiculos').innerText =
            data.vehiculos || 0;

        document.getElementById('totalDocumentos').innerText =
            data.documentos || 0;

    }
    catch(error) {

        console.error(error);

    }

};