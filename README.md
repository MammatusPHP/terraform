# Terraform

![Continuous Integration](https://github.com/MammatusPHP/terraform/workflows/Continuous%20Integration/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/mammatus/terraform/v/stable.png)](https://packagist.org/packages/mammatus/terraform)
[![Total Downloads](https://poser.pugx.org/mammatus/terraform/downloads.png)](https://packagist.org/packages/mammatus/terraform/stats)
[![License](https://poser.pugx.org/mammatus/terraform/license.png)](https://packagist.org/packages/mammatus/terraform)

Tooling to pass data from Mammatus based apps to Terraform.

# Install

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require mammatus/terraform
```

# Usage

Collect data from your Mammatus application and export it as **HCL tfvars lines** for GitHub Actions workflows or `terraform.tfvars` files.

Register variables through a `Variables` event listener in your Mammatus app:

```php
use Mammatus\Terraform\Events\Variables;
use Mammatus\Terraform\Events\Variables\Registry\Entry;

Variables::class => [
    static function (Variables $variables): void {
        $variables->add(new Entry('app_name', 'mammatus-demo'));
        $variables->add(new Entry('replicas', 3));
    },
],
```

Export tfvars from the CLI:

```bash
vendor/bin/mammatus-terraform-tfvars
```

Or from PHP:

```php
use Mammatus\Container\Factory;
use Mammatus\Terraform\Tfvars;

Factory::create()->get(Tfvars::class)->export();
```

## HCL tfvars export

The tfvars export produces assignment lines that can be pasted directly into a `terraform.tfvars` file or a GitHub Actions `terraformVars` block:

```
app_name = "mammatus-demo"
replicas = 3
```

Environment variable references from your Mammatus app are preserved as strings:

```php
$variables->add(new Entry('rabbitmq_vhost', '$HOME_RABBITMQ_VHOST'));
```

```
rabbitmq_vhost = "$HOME_RABBITMQ_VHOST"
```

# Passing data to Terraform

## GitHub Actions `terraformVars` (recommended for WyriHaximus workflows)

[WyriHaximus/github-workflows](https://github.com/WyriHaximus/github-workflows) writes the `terraformVars` input into `terraform.tfvars` during release management. Use `toTfvars()` to inject Mammatus-generated entries between your static variables.

Generate tfvars from your app in a dedicated job:

```yaml
jobs:
  terraform-mammatus-tfvars:
    name: Generate MammatusPHP tfvars for Terraform
    runs-on: chaos-fast
    outputs:
      terraform-tfvars: ${{ steps.terraform-mammatus-tfvars.outputs.terraform-tfvars }}
    steps:
      - uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1
      - name: Generate Mammatus tfvars for Terraform
        id: terraform-mammatus-tfvars
        run: echo "terraform-tfvars<<EOF" >> "$GITHUB_OUTPUT" && vendor/bin/mammatus-terraform-tfvars >> "$GITHUB_OUTPUT" && echo "EOF" >> "$GITHUB_OUTPUT"
```

Pass the generated lines into `terraformVars` between your static entries:

```yaml
  release-managment:
    needs:
      - terraform-mammatus-tfvars
    uses: WyriHaximus/github-workflows/.github/workflows/project-release-management.yaml@main
    with:
      terraformVars: |
        kubernetes_config_path   = "~/.kube/config"
        kubernetes_context       = "$HOME_KUBE_CONTEXT"
        kubernetes_namespace     = "$HOME_KUBE_NAMESPACE"
        rabbitmq_tf_username     = "$HOME_RABBITMQ_TF_USERNAME"
        ${{ needs.terraform-mammatus-tfvars.outputs.terraform-tfvars }}
        rabbitmq_scaler_password = "$HOME_RABBITMQ_SCALER_PASSWORD"
        rabbitmq_scaler_username = "$HOME_RABBITMQ_SCALER_USERNAME"
      terraformDirectory: ".terraform/prod/"
```

## `terraform.tfvars` file

Write the export to a file and run Terraform as usual:

```bash
vendor/bin/mammatus-terraform-tfvars > terraform.tfvars
terraform plan
```

# Development

```bash
make install
make
```

See `AGENTS.md` for contributor workflow details.

# License

The MIT License (MIT)

Copyright (c) 2026 Cees-Jan Kiewiet

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
