-- Rode este arquivo no phpMyAdmin (aba "SQL") do banco que você já criou,
-- para trocar o login de "e-mail + senha" para "nome de usuário + senha".
-- Só precisa rodar isso UMA VEZ.

ALTER TABLE usuarios MODIFY email VARCHAR(160) NULL;
ALTER TABLE usuarios ADD UNIQUE KEY uniq_nome (nome);
