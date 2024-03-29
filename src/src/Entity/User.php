<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Controller\ResetPasswordAction;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;


/**
 * Make some operations enable with item/collectionOperations (check api debug:router with the php console to see routes)
 * Here, get on item route and post on collection route are allow
 * 
 * Make some fields disallowed in 
 * Adding access control has the same effect as access_control in the security.yaml to get this entity
 * 
 * The normalization context name inside an operation brackets is named with snake_case 
 * instead of camelCase (which is used when the normalization annotation is outside of operations brackets)
 * 
 * We need to specify the default group (l.28) to avoid some fields returned by the get route
 * 
 * @ApiResource(
 *      normalizationContext={"groups"={"get"}},
 *      itemOperations={
 *          "get"={
 *              "access_control"="is_granted('IS_AUTHENTICATED_FULLY')",
 *              "normalization_context"={
 *                  "groups"={"get"}
 *              }
 *          },
 *          "put"={
 *              "access_control"="is_granted('IS_AUTHENTICATED_FULLY') and object == user",
 *              "denormalization_context"={
 *                  "groups"={"put"}
 *              },
 *              "normalization_context"={
 *                  "groups"={"get"}
 *              }
 *          },
 *          "put-reset-password"={
 *              "access_control"="is_granted('IS_AUTHENTICATED_FULLY') and object == user",
 *              "method"="PUT",
 *              "path"="/users/{id}/reset-password",
 *              "controller"=ResetPasswordAction::class,
 *              "denormalization_context"={
 *                  "groups"={"put-reset-password"}
 *              },
 *              "validation_groups"={
 *                  "put-reset-password"
 *              }
 *          },
 *          "delete"={
 *              "access_control"="is_granted('IS_AUTHENTICATED_FULLY')",
 *              "method"="DELETE",
 *              "denormalization_context"={
 *                  "groups"={"delete-user"}
 *              }
 *          }
 *      },
 *      collectionOperations={
 *          "post"={
 *              "denormalization_context"={
 *                  "groups"={"post"}
 *              },
 *              "normalization_context"={
 *                  "groups"={"get"}
 *              },
 *              "validation_groups"={
 *                  "post"
 *              }
 *          }
 *      },
 * )
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * 
 * Make entity unique by fields specified.
 * Here, unicity of username and email, separatly, are checked (can't add a user with same username in DB, or same email)
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 * 
 * This make association fields must be unique, not one and another one 
 * (the user can be add if its username AND email are not both present in a same user record in the db)
 * @UniqueEntity(fields={"username", "email"})
 */
class User implements UserInterface
{

    const ROLE_COMMENTATOR = 'ROLE_COMMENTATOR';
    const ROLE_WRITER = 'ROLE_WRITER';
    const ROLE_EDITOR = 'ROLE_EDITOR';
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_SUPERADMIN = 'ROLE_SUPERADMIN';

    const DEFAULT_ROLES = [self::ROLE_COMMENTATOR];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * 
     * This annotation allows field to be fetched by api, this will return each field annotated
     * @Groups({"get", "delete-user"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     *  
     * The Groups annotation need to be imported
     * 
     * The group "get-comment-with-author" is defined in Comment entity
     * this allows the comment Get route to access at related user informations we will give 
     * 
     * @Groups({"get", "post", "get-comment-with-author", "get-blog-post-with-author"})
     * @Assert\NotBlank(groups={"post"})
     * @Assert\Length(min=4, max=255)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"post"})
     * @Groups({"post"})
     * 
     * The Regex check is to specify the input size at least, and set up the check about 1 digit, 1 uppercase/lowercase char, symbols etc
     * @Assert\Regex(
     *      pattern="/(?=.+[A-Z])(?=.+[a-z])(?=.*\d).{7,}/",
     *      message="Password must be 8 chars long and contains 1 digit, 1 uppercase, 1 lowercase ",
     *      groups={"post"}
     * )
     */
    private $password;

    /**
     * @Assert\NotBlank(groups={"post"})
     * @Groups({"post"})
     * 
     * Expressions allows you to use some code check as below, which compare the current password with its checker
     * @Assert\Expression(
     *      "this.getPassword() === this.getRetypedPassword()",
     *      message="Passwords does not match",
     *      groups={"post"}
     * )
     */
    private $retypedPassword;

    /**
     * @Assert\NotBlank(groups={"put-reset-password"})
     * @Groups({"put-reset-password"})
     * 
     * The Regex check is to specify the input size at least, and set up the check about 1 digit, 1 uppercase/lowercase char, symbols etc
     * @Assert\Regex(
     *      pattern="/(?=.+[A-Z])(?=.+[a-z])(?=.*\d).{7,}/",
     *      message="Password must be 8 chars long and contains 1 digit, 1 uppercase, 1 lowercase",
     *      groups={"put-reset-password"}
     * )
     */
    private $newPassword;

    /**
     * @Groups({"put-reset-password"})
     * @Assert\NotBlank(groups={"put-reset-password"})
     * 
     * Expressions allows you to use some code check as below, which compare the current password with its checker
     * @Assert\Expression(
     *      "this.getNewPassword() === this.getNewRetypedPassword()",
     *      message="Passwords does not match",
     *      groups={"put-reset-password"}
     * )
     */
    private $newRetypedPassword;

    /**
     * @Groups({"put-reset-password"})
     * @Assert\NotBlank(groups={"put-reset-password"})
     * @UserPassword(groups={"put-reset-password"})
     */
    private $oldPassword;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $passwordChangeDate;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"get", "put", "post", "get-comment-with-author", "get-blog-post-with-author"})
     * @Assert\NotBlank(groups={"post", "put"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"post", "put"})
     * @Groups({"put", "post", "get-admin", "get-owner"})
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="author")
     * @Groups({"get"})
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\BlogPost", mappedBy="author")
     * @Groups({"get"})
     */
    private $posts;

    /**
     * @ORM\Column(type="simple_array", length=200)
     * @Groups({"get-admin", "get-owner"})
     */
    private $roles;

    /**
     * Confirm if the user has its account activated
     * @ORM\Column(type="boolean")
     */
    private $enable;

    /**
     * The first token the user will provide with his first login
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $confirmationToken;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->roles = self::DEFAULT_ROLES;
        $this->enable = false;
        $this->confirmationToken = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setAuthor($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getAuthor() === $this) {
                $comment->setAuthor($this);
            }
        }

        return $this;
    }

    /**
     * @return Collection|BlogPost[]
     */ 
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /**
     * Set the value of posts
     *
     * @return  self
     */ 
    public function setPosts(BlogPost $posts): self
    {
        $this->posts = $posts;

        return $this;
    }

    public function getSalt()
    {
        return null;
    }

    public function eraseCredentials()
    {

    }

    public function getRetypedPassword(): ?string
    {
        return $this->retypedPassword;
    }

    public function setRetypedPassword(string $retypedPassword): self
    {
        $this->retypedPassword = $retypedPassword;

        return $this;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    public function getOldPassword()
    {
        return $this->oldPassword;
    }

    public function setOldPassword($oldPassword)
    {
        $this->oldPassword = $oldPassword;
    }

    public function getNewRetypedPassword()
    {
        return $this->newRetypedPassword;
    }

    public function setNewRetypedPassword($newRetypedPassword)
    {
        $this->newRetypedPassword = $newRetypedPassword;
    }

    public function getNewPassword()
    {
        return $this->newPassword;
    }

    public function setNewPassword($newPassword)
    {
        $this->newPassword = $newPassword;
    }

    public function getPasswordChangeDate()
    {
        return $this->passwordChangeDate;
    }

    public function setPasswordChangeDate($passwordChangeDate)
    {
        $this->passwordChangeDate = $passwordChangeDate;
    }

    public function getEnable()
    {
        return $this->enable;
    }

    public function setEnable($enable)
    {
        $this->enable = $enable;
    }

    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;
    }
}
