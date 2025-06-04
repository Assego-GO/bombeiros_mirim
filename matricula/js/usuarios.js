// Variáveis globais
let userPermissions = {};
let isAdmin = false;

document.addEventListener("DOMContentLoaded", () => {
  // Carregar permissões do usuário
  carregarPermissoes().then(() => {
    // Verificar se é admin
    if (!isAdmin) {
      alert('Apenas administradores podem acessar esta página.');
      window.location.href = 'index.php';
      return;
    }
    
    // Carregar lista de usuários
    carregarUsuarios();
  });
  
  // Botão Novo Usuário
  const btnNovoUsuario = document.getElementById('novo-usuario-btn');
  if (btnNovoUsuario) {
    btnNovoUsuario.addEventListener('click', function() {
      document.getElementById('novo-usuario-modal').style.display = 'flex';
    });
  }
  
  // Formulário Novo Usuário
  const formNovoUsuario = document.getElementById('novo-usuario-form');
  if (formNovoUsuario) {
    formNovoUsuario.addEventListener('submit', function(e) {
      e.preventDefault();
      criarNovoUsuario(this);
    });
  }
  
  // Fecha modais quando clicar fora deles
  document.querySelectorAll('.modal-backdrop').forEach(modal => {
    modal.addEventListener('click', function(e) {
      if (e.target === this) {
        this.style.display = 'none';
      }
    });
  });
});

// Função para carregar permissões do usuário
async function carregarPermissoes() {
  try {
    const response = await fetch('api/get_permissoes.php');
    const data = await response.json();
    
    if (data.status === 'sucesso') {
      userPermissions = data.permissoes;
      isAdmin = data.is_admin;
      return true;
    } else {
      console.error('Erro ao carregar permissões:', data.mensagem);
      return false;
    }
  } catch (err) {
    console.error('Erro ao carregar permissões:', err);
    return false;
  }
}

// Função para carregar usuários
function carregarUsuarios() {
  showLoading();
  
  fetch('api/listar_usuarios.php')
    .then(res => res.json())
    .then(usuarios => {
      const tbody = document.getElementById('usuarios-body');
      tbody.innerHTML = '';
      
      usuarios.forEach(usuario => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${usuario.nome}</td>
          <td>${usuario.email}</td>
          <td>${usuario.perfil}</td>
          <td>
            <span class="status ${usuario.ativo ? 'status-ativo' : 'status-inativo'}">
              ${usuario.ativo ? 'Ativo' : 'Inativo'}
            </span>
          </td>
          <td>${formatarData(usuario.ultimo_login)}</td>
          <td>
            <button class="action-btn editar-usuario-btn" title="Editar" data-id="${usuario.id}">
              <i class="fas fa-edit"></i>
            </button>
            <button class="action-btn excluir-usuario-btn" title="Excluir" data-id="${usuario.id}">
              <i class="fas fa-trash-alt"></i>
            </button>
          </td>
        `;
        tbody.appendChild(tr);
      });
      
      adicionarEventosAosBotoes();
      hideLoading();
    })
    .catch(err => {
      console.error('Erro ao carregar usuários:', err);
      alert('Erro ao carregar lista de usuários.');
      hideLoading();
    });
}

// Função para criar novo usuário
function criarNovoUsuario(form) {
  showLoading();
  
  const formData = new FormData(form);
  const dados = {};
  
  formData.forEach((valor, chave) => {
    if (chave === 'ativo') {
      dados[chave] = valor === 'on' ? 1 : 0;
    } else {
      dados[chave] = valor;
    }
  });
  
  fetch('api/novo_usuario.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(dados)
  })
    .then(res => {
      if (!res.ok) {
        return res.text().then(text => {
          throw new Error(`Erro HTTP ${res.status}: ${text}`);
        });
      }
      return res.json();
    })
    .then(resp => {
      hideLoading();
      
      if (resp.status === 'sucesso') {
        alert('Usuário criado com sucesso!');
        document.getElementById('novo-usuario-modal').style.display = 'none';
        form.reset();
        carregarUsuarios();
      } else {
        alert('Erro ao criar usuário: ' + resp.mensagem);
      }
    })
    .catch(err => {
      hideLoading();
      console.error('Erro ao criar usuário:', err);
      alert('Erro inesperado ao criar usuário: ' + err.message);
    });
}

// Função para adicionar eventos aos botões
function adicionarEventosAosBotoes() {
  document.querySelectorAll('.editar-usuario-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      editarUsuario(id);
    });
  });
  
  document.querySelectorAll('.excluir-usuario-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      if (confirm('Tem certeza que deseja excluir este usuário?')) {
        excluirUsuario(id);
      }
    });
  });
}

// Funções utilitárias
function showLoading() {
  const loadingOverlay = document.getElementById('loading-overlay');
  if (loadingOverlay) {
    loadingOverlay.style.display = 'flex';
  }
}

function hideLoading() {
  const loadingOverlay = document.getElementById('loading-overlay');
  if (loadingOverlay) {
    loadingOverlay.style.display = 'none';
  }
}

function formatarData(dataString) {
  if (!dataString) return '-';
  
  const data = new Date(dataString);
  if (isNaN(data.getTime())) return dataString;
  
  return data.toLocaleDateString('pt-BR') + ' ' + 
         data.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
}