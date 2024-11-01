<?php

namespace VinCSS\WebAuthn;

use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialSource;

class Credential implements PublicKeyCredentialSourceRepository
{
    public function findOneMetaByCredentialId(string $publicKeyCredentialId): ?array
    {
        $meta = json_decode(getOption("user_credentials_meta"), true);
        if (isset($meta[base64_encode($publicKeyCredentialId)])) {
            return $meta[base64_encode($publicKeyCredentialId)];
        }
        return null;
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $data = $this->getCredential();
        if (isset($data[base64_encode($publicKeyCredentialId)])) {
            return PublicKeyCredentialSource::createFromArray($data[base64_encode($publicKeyCredentialId)]);
        }
        return null;
    }

    public function findCredentialsForUserEntityByType(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity, string $credentialType): array
    {
        $credentialsForUserEntity = $this->findAllForUserEntity($publicKeyCredentialUserEntity);
        $credentialsByType = [];
        foreach ($credentialsForUserEntity as $credential) {
            if ($this->findOneMetaByCredentialId($credential->getPublicKeyCredentialId())["authenticator_type"] === $credentialType) {
                $credentialsByType[] = $credential;
            }
        }
        return $credentialsByType;
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $sources = [];
        foreach ($this->getCredential() as $data) {
            $source = PublicKeyCredentialSource::createFromArray($data);
            if ($source->getUserHandle() === $publicKeyCredentialUserEntity->getId()) {
                $sources[] = $source;
            }
        }
        return $sources;
    }


    private function saveCredential(array $data, string $key, bool $usernameless = false): void
    {
        $_POST = sanitizeArray($_POST);
        if (isset($_POST["type"]) && ($_POST["type"] === "platform" || $_POST["type"] == "cross-platform" || $_POST["type"] === "none") && $key !== '') {
            $source = $data[$key]->getUserHandle();
            $meta = json_decode(getOption("user_credentials_meta"), true);
            $meta[$key] = array("human_name" => base64_encode(sanitize_text_field($_POST["name"])), "added" => date('Y-m-d H:i:s', current_time('timestamp')), "authenticator_type" => $_POST["type"], "user" => $source, "usernameless" => $usernameless, "last_used" => "-");
            updateOption("user_credentials_meta", json_encode($meta));
        }
        updateOption("user_credentials", json_encode($data));
    }

    public function updateCredentialLastUsed(string $publicKeyCredentialId): void
    {
        $credential = $this->findOneMetaByCredentialId($publicKeyCredentialId);
        if ($credential !== null) {
            $credential["last_used"] = date('Y-m-d H:i:s', current_time('timestamp'));
            $meta = json_decode(getOption("user_credentials_meta"), true);
            $meta[base64_encode($publicKeyCredentialId)] = $credential;
            updateOption("user_credentials_meta", json_encode($meta));
        }
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, bool $usernameless = false): void
    {
        $data = $this->getCredential();
        $data_key = base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId());
        $data[$data_key] = $publicKeyCredentialSource;
        $this->saveCredential($data, $data_key, $usernameless);
    }


    public function modifyAuthenticator(string $id, string $name, PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity, string $action, string $res_id): string
    {
        $keys = $this->findAllForUserEntity($publicKeyCredentialUserEntity);
        $user_id = $publicKeyCredentialUserEntity->getId();

        // Check if the user has the authenticator
        foreach ($keys as $item) {
            if ($item->getUserHandle() === $user_id) {
                if (base64_encode($item->getPublicKeyCredentialId()) === base64_decode(str_pad(strtr($id, '-_', '+/'), strlen($id) % 4, '=', STR_PAD_RIGHT))) {
                    if ($action === "rename") {
                        $this->renameCredential(base64_encode($item->getPublicKeyCredentialId()), $name, $res_id);
                    } else if ($action === "remove") {
                        $this->removeCredential(base64_encode($item->getPublicKeyCredentialId()), $res_id);
                    }
                    addLog($res_id, "ajax_modify_authenticator: Done");
                    return "true";
                }
            }
        }
        addLog($res_id, "ajax_modify_authenticator: (ERROR) Authenticator not found, exit");
        return "Not Found.";
    }


    public function getShowList(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $data = json_decode(getOption("user_credentials_meta"), true);
        $arr = array();
        $user_id = $publicKeyCredentialUserEntity->getId();
        foreach ($data as $key => $value) {
            if ($user_id === $value["user"]) {
                array_push($arr, array(
                    "key" => rtrim(strtr(base64_encode($key), '+/', '-_'), '='),
                    "name" => base64_decode($value["human_name"]),
                    "type" => $value["authenticator_type"],
                    "added" => $value["added"],
                    "usernameless" => isset($value["usernameless"]) ? $value["usernameless"] : false,
                    "last_used" => isset($value["last_used"]) ? $value["last_used"] : "-"
                ));
            }
        }
        return array_map(function ($item) {
            return array("key" => $item["key"], "name" => $item["name"], "type" => $item["type"], "added" => $item["added"], "usernameless" => $item["usernameless"], "last_used" => $item["last_used"]);
        }, $arr);
    }

    private function removeCredential(string $id, string $res_id): void
    {
        $data = $this->getCredential();
        unset($data[$id]);
        $this->saveCredential($data, '');
        $meta = json_decode(getOption("user_credentials_meta"), true);
        addLog($res_id, "ajax_modify_authenticator: Remove \"" . base64_decode($meta[$id]["human_name"]) . "\"");
        unset($meta[$id]);
        updateOption("user_credentials_meta", json_encode($meta));
    }

    private function renameCredential(string $id, string $name, string $res_id): void
    {
        $meta = json_decode(getOption("user_credentials_meta"), true);
        addLog($res_id, "ajax_modify_authenticator: Rename \"" . base64_decode($meta[$id]["human_name"]) . "\" -> \"" . $name . "\"");
        $meta[$id]["human_name"] = base64_encode($name);
        updateOption("user_credentials_meta", json_encode($meta));
    }

    private function getCredential(): array
    {
        if (getOption("user_credentials") !== NULL) {
            try {
                return json_decode(getOption("user_credentials"), true);
            } catch (\Throwable $exception) {
                return [];
            }
        }
        return [];
    }
}
